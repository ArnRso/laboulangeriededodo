<?php

namespace App\Entity;

use App\Enum\AppKind;
use App\Enum\MediaType;
use App\Repository\MediaRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
    /**
     * Un mois d'attente : au-delà, la valeur relève de la faute de frappe.
     */
    public const int MAX_DELAY_MINUTES = 720 * 60;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private int $position = 0;

    /**
     * L'application imitée. Absente tant que la notification est un brouillon :
     * on remplit d'abord, on habille ensuite.
     */
    #[ORM\Column(nullable: true, enumType: AppKind::class)]
    private ?AppKind $appKind = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
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
    #[Assert\Length(max: 2048)]
    private ?string $url = null;

    /**
     * Minutes d'attente après l'ouverture de la notification précédente.
     * Sans effet sur la première du fil, disponible immédiatement.
     */
    #[ORM\Column]
    #[Assert\PositiveOrZero]
    #[Assert\LessThanOrEqual(self::MAX_DELAY_MINUTES)]
    private int $delayMinutes = 1440;

    /**
     * Détails propres à l'application imitée (pseudo Instagram, praticien
     * Doctolib…), dont la forme est dictée par le formulaire de chaque app.
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: 'json')]
    private array $appData = [];

    /**
     * Bouts de texte préparés sans savoir encore où ils iront : ils se placent
     * dans les champs de l'app au moment de l'habillage.
     *
     * @var list<array{label: string, text: string}>
     */
    #[ORM\Column(type: 'json')]
    private array $fragments = [];

    /**
     * Étiquettes de rangement, pour retrouver ses notifications dans le
     * back-office. Le destinataire ne les voit jamais.
     *
     * @var Collection<int, Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'medias')]
    #[ORM\OrderBy(['name' => 'ASC'])]
    private Collection $tags;

    #[ORM\Column]
    private bool $published = true;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    #[Vich\UploadableField(mapping: 'media_file', fileNameProperty: 'filePath', originalName: 'originalName')]
    private ?File $file = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
        $this->tags = new ArrayCollection();
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

    public function getAppKind(): ?AppKind
    {
        return $this->appKind;
    }

    public function setAppKind(?AppKind $appKind): static
    {
        $this->appKind = $appKind;

        return $this;
    }

    /**
     * L'application, là où le code a la garantie qu'elle est choisie : rendre
     * l'écran d'un brouillon serait une erreur de programmation, pas un cas.
     */
    public function requireAppKind(): AppKind
    {
        if (null === $this->appKind) {
            throw new \LogicException(sprintf('La notification « %s » est un brouillon : elle n\'a pas encore d\'application.', $this->title));
        }

        return $this->appKind;
    }

    public function isDraft(): bool
    {
        return null === $this->appKind;
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

    /**
     * @return list<array{label: string, text: string}>
     */
    public function getFragments(): array
    {
        return $this->fragments;
    }

    /**
     * Les lignes sans texte sont écartées : une étiquette seule ne se place
     * nulle part.
     *
     * @param list<array<string, mixed>> $fragments
     */
    public function setFragments(array $fragments): static
    {
        $this->fragments = [];

        foreach ($fragments as $fragment) {
            $label = $fragment['label'] ?? '';
            $text = $fragment['text'] ?? '';

            $this->addFragment(\is_string($label) ? $label : '', \is_string($text) ? $text : '');
        }

        return $this;
    }

    /**
     * Un même texte n'est gardé qu'une fois : l'habillage archive ce qu'il
     * abandonne, et peut repasser plusieurs fois sur la même notification.
     */
    public function addFragment(string $label, string $text): static
    {
        $text = trim($text);

        if ('' === $text) {
            return $this;
        }

        foreach ($this->fragments as $existing) {
            if ($existing['text'] === $text) {
                return $this;
            }
        }

        $this->fragments[] = ['label' => trim($label), 'text' => $text];

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    /**
     * Les deux côtés sont tenus à jour : Doctrine n'écoute que celui-ci pour
     * écrire, mais l'étiquette doit connaître ses notifications sans attendre
     * un rechargement.
     */
    public function addTag(Tag $tag): static
    {
        if (!$this->tags->contains($tag)) {
            $this->tags->add($tag);
            $tag->getMedias()->add($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): static
    {
        if ($this->tags->removeElement($tag)) {
            $tag->getMedias()->removeElement($this);
        }

        return $this;
    }

    public function hasTag(Tag $tag): bool
    {
        return $this->tags->contains($tag);
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
        // Un brouillon peut attendre son souvenir ; seule la cohérence d'un
        // fichier déjà déposé reste vérifiée. La règle complète s'applique dès
        // qu'une application est choisie, donc avant toute mise dans le fil.
        if (null === $this->appKind) {
            $this->validateFileMatchesType($context);

            return;
        }

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
     * Sans application, il n'y a pas d'écran à montrer : un brouillon ne peut
     * pas être dans le fil.
     */
    #[Assert\Callback]
    public function validateDraftStaysHidden(ExecutionContextInterface $context): void
    {
        if (null === $this->appKind && $this->published) {
            $context->buildViolation('Un brouillon ne peut pas être dans le fil : choisis d\'abord son application.')
                ->atPath('published')
                ->addViolation();
        }
    }

    /**
     * Le type MIME est confronté au type choisi : l'onglet indique l'intention,
     * mais rien n'empêche d'y déposer un fichier d'une autre nature.
     */
    private function validateFileMatchesType(ExecutionContextInterface $context): void
    {
        $prefix = $this->type->mimePrefix();

        // Un champ fichier laissé vide arrive quand même, sous la forme d'un
        // envoi en erreur au chemin vide : il n'y a alors rien à inspecter.
        if (null === $this->file || null === $prefix || !is_file($this->file->getPathname())) {
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
