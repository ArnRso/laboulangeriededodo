<?php

namespace App\Entity;

use App\Enum\AppKind;
use App\Enum\MediaType;
use App\Repository\MediaRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Vich\UploaderBundle\Mapping\Attribute as Vich;

/**
 * Une notification du fil : un souvenir déguisé en message d'une application
 * connue, qui arrive un certain temps après l'ouverture de la précédente.
 */
#[ORM\Entity(repositoryClass: MediaRepository::class)]
#[Vich\Uploadable]
class Media
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $position = 0;

    #[ORM\Column(enumType: AppKind::class)]
    private AppKind $appKind = AppKind::UBER_EATS;

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

    /**
     * Minutes d'attente après l'ouverture de la notification précédente.
     * Sans effet sur la première du fil, disponible immédiatement.
     */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $delayMinutes = 1440;

    /**
     * Aura gagnée — ou perdue — à l'ouverture. Négatif volontairement possible :
     * certaines décisions étaient objectivement catastrophiques.
     */
    #[ORM\Column]
    private int $auraPoints = 100;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $auraMessage = null;

    /**
     * Détails propres à l'application imitée (pseudo Instagram, praticien
     * Doctolib…), dont la forme est dictée par le formulaire de chaque app.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $appData = [];

    #[ORM\Column]
    private bool $published = true;

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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    public function getAppKind(): AppKind
    {
        return $this->appKind;
    }

    public function setAppKind(AppKind $appKind): static
    {
        $this->appKind = $appKind;

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

    public function getDelayMinutes(): int
    {
        return $this->delayMinutes;
    }

    public function setDelayMinutes(int $delayMinutes): static
    {
        $this->delayMinutes = $delayMinutes;

        return $this;
    }

    public function getDelayHoursPart(): int
    {
        return intdiv($this->delayMinutes, 60);
    }

    public function getDelayMinutesPart(): int
    {
        return $this->delayMinutes % 60;
    }

    /**
     * Délai formaté pour l'affichage, par exemple « 1 h 30 » ou « 45 min ».
     */
    public function getDelayLabel(): string
    {
        $hours = $this->getDelayHoursPart();
        $minutes = $this->getDelayMinutesPart();

        if (0 === $hours) {
            return sprintf('%d min', $minutes);
        }

        if (0 === $minutes) {
            return sprintf('%d h', $hours);
        }

        return sprintf('%d h %02d', $hours, $minutes);
    }

    public function getAuraPoints(): int
    {
        return $this->auraPoints;
    }

    public function setAuraPoints(int $auraPoints): static
    {
        $this->auraPoints = $auraPoints;

        return $this;
    }

    public function getAuraMessage(): ?string
    {
        return $this->auraMessage;
    }

    public function setAuraMessage(?string $auraMessage): static
    {
        $this->auraMessage = $auraMessage;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAppData(): array
    {
        return $this->appData;
    }

    /**
     * @param array<string, mixed> $appData
     */
    public function setAppData(array $appData): static
    {
        $this->appData = $appData;

        return $this;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): static
    {
        $this->published = $published;

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
     * Chaque type de média a besoin de son propre contenu : sans cette règle, une
     * notification vide se retrouverait dans le fil et bloquerait la progression.
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

        $this->validateFileMatchesType($context);
    }

    /**
     * Le type MIME est confronté au type choisi : l'onglet indique l'intention,
     * mais rien n'empêche d'y déposer un fichier d'une autre nature.
     */
    private function validateFileMatchesType(ExecutionContextInterface $context): void
    {
        $prefix = $this->type->mimePrefix();

        if (null === $this->file || null === $prefix) {
            return;
        }

        $mimeType = $this->file->getMimeType();

        if (null !== $mimeType && !str_starts_with($mimeType, $prefix)) {
            $context->buildViolation('Ce fichier ne correspond pas au type « {{ type }} » (détecté : {{ mime }}).')
                ->setParameter('{{ type }}', $this->type->label())
                ->setParameter('{{ mime }}', $mimeType)
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
