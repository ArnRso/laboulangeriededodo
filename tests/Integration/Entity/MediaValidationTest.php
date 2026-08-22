<?php

namespace App\Tests\Integration\Entity;

use App\Entity\Media;
use App\Entity\Pack;
use App\Enum\MediaType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class MediaValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->validator = self::getContainer()->get(ValidatorInterface::class);
    }

    public function testLinkMediaRequiresUrl(): void
    {
        $media = $this->createMedia(MediaType::LINK);

        self::assertViolation($this->validator->validate($media), 'url');
    }

    public function testLinkMediaWithUrlIsValid(): void
    {
        $media = $this->createMedia(MediaType::LINK);
        $media->setUrl('https://example.com');

        self::assertCount(0, $this->validator->validate($media));
    }

    public function testTextMediaRequiresContent(): void
    {
        $media = $this->createMedia(MediaType::TEXT);

        self::assertViolation($this->validator->validate($media), 'textContent');
    }

    public function testTextMediaRejectsWhitespaceOnlyContent(): void
    {
        $media = $this->createMedia(MediaType::TEXT);
        $media->setTextContent("   \n  ");

        self::assertViolation($this->validator->validate($media), 'textContent');
    }

    public function testImageMediaRequiresFile(): void
    {
        $media = $this->createMedia(MediaType::IMAGE);

        self::assertViolation($this->validator->validate($media), 'file');
    }

    public function testImageMediaWithExistingPathIsValid(): void
    {
        $media = $this->createMedia(MediaType::IMAGE);
        $media->setFilePath('deja-uploade.jpg');

        self::assertCount(0, $this->validator->validate($media));
    }

    public function testTitleIsRequired(): void
    {
        $media = $this->createMedia(MediaType::TEXT);
        $media->setTitle('')->setTextContent('du contenu');

        self::assertViolation($this->validator->validate($media), 'title');
    }

    private function createMedia(MediaType $type): Media
    {
        $pack = new Pack();
        $pack->setName('Pack');

        $media = new Media();
        $media->setPack($pack)->setTitle('Titre')->setType($type);

        return $media;
    }

    /**
     * @param iterable<\Symfony\Component\Validator\ConstraintViolationInterface> $violations
     */
    private static function assertViolation(iterable $violations, string $expectedPath): void
    {
        $paths = [];

        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        self::assertContains($expectedPath, $paths, sprintf('Une violation était attendue sur "%s".', $expectedPath));
    }
}
