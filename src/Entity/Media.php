<?php

namespace App\Entity;

use App\Enum\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[Vich\Uploadable]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'medias')]
    #[ORM\JoinColumn(nullable: false)]
    private Pack $pack;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(enumType: MediaType::class)]
    private MediaType $type = MediaType::TEXT;

    #[ORM\Column(nullable: true)]
    private ?string $filePath = null;

    #[ORM\Column(nullable: true)]
    private ?string $originalName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $textContent = null;

    #[ORM\Column(length: 2048, nullable: true)]
    #[Assert\Url]
    private ?string $url = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[Vich\UploadableField(mapping: 'media_file', fileNameProperty: 'filePath', originalName: 'originalName')]
    private ?File $file = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPack(): Pack
    {
        return $this->pack;
    }

    public function setPack(Pack $pack): static
    {
        $this->pack = $pack;

        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getType(): MediaType
    {
        return $this->type;
    }

    public function setType(MediaType $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): static
    {
        $this->filePath = $filePath;

        return $this;
    }

    public function getOriginalName(): ?string
    {
        return $this->originalName;
    }

    public function setOriginalName(?string $originalName): static
    {
        $this->originalName = $originalName;

        return $this;
    }

    public function getTextContent(): ?string
    {
        return $this->textContent;
    }

    public function setTextContent(?string $textContent): static
    {
        $this->textContent = $textContent;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    /**
     * Chaque type de média a besoin de son propre contenu : sans cette règle, un
     * média vide se retrouverait dans un pack et bloquerait la progression.
     */
    #[Assert\Callback]
    public function validateContent(ExecutionContextInterface $context): void
    {
        if (MediaType::LINK === $this->type && null === $this->url) {
            $context->buildViolation('Un média de type Lien doit avoir une URL.')
                ->atPath('url')
                ->addViolation();
        }

        if (MediaType::TEXT === $this->type && (null === $this->textContent || '' === trim($this->textContent))) {
            $context->buildViolation('Un média de type Texte doit avoir du contenu.')
                ->atPath('textContent')
                ->addViolation();
        }

        if ($this->type->isFile() && null === $this->filePath && null === $this->file) {
            $context->buildViolation('Un média de ce type doit avoir un fichier.')
                ->atPath('file')
                ->addViolation();
        }
    }

    public function setFile(?File $file): static
    {
        $this->file = $file;

        if (null !== $file) {
            $this->updatedAt = new \DateTimeImmutable();
        }

        return $this;
    }
}
