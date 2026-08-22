<?php

namespace App\Tests\Integration\Entity;

use App\Entity\Media;
use App\Entity\Pack;
use App\Enum\MediaType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
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

    public function testFileMustMatchTheChosenType(): void
    {
        $media = $this->createMedia(MediaType::IMAGE);
        $media->setFile($this->createUploadedFile('sequence.mp4', 'video/mp4', 'du texte, pas une image'));

        self::assertViolation($this->validator->validate($media), 'file');
    }

    public function testMatchingFileIsAccepted(): void
    {
        $media = $this->createMedia(MediaType::IMAGE);
        $media->setFile($this->createUploadedFile('souvenir.png', 'image/png', $this->pngContent()));

        self::assertCount(0, $this->validator->validate($media));
    }

    public function testARenamedFileIsStillRefused(): void
    {
        $media = $this->createMedia(MediaType::IMAGE);
        // Un fichier texte renommé en .png : seul son contenu fait foi.
        $media->setFile($this->createUploadedFile('piege.png', 'image/png', 'ceci est du texte'));

        self::assertViolation($this->validator->validate($media), 'file');
    }

    public function testAudioFileIsRefusedAsAVideo(): void
    {
        $media = $this->createMedia(MediaType::VIDEO);
        $media->setFile($this->createUploadedFile('chanson.mp3', 'audio/mpeg', $this->pngContent()));

        self::assertViolation($this->validator->validate($media), 'file');
    }

    public function testTitleIsRequired(): void
    {
        $media = $this->createMedia(MediaType::TEXT);
        $media->setTitle('')->setTextContent('du contenu');

        self::assertViolation($this->validator->validate($media), 'title');
    }

    /**
     * Le contenu compte : getMimeType() inspecte les octets du fichier plutôt
     * que son extension, ce qui est justement ce qui déjoue un renommage.
     */
    private function createUploadedFile(string $name, string $mimeType, string $content): UploadedFile
    {
        $directory = sys_get_temp_dir().'/'.uniqid('media-mime-', true);
        mkdir($directory);
        $path = $directory.'/'.$name;
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, $mimeType, null, true);
    }

    private function pngContent(): string
    {
        return (string) base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
            true,
        );
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
