<?php

namespace App\Service\Dressing;

use App\Enum\AppFieldKind;
use App\Enum\AppKind;
use App\Form\AppDetails\AppDetailsRegistry;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Les champs de détails d'une app, lus dans son formulaire plutôt que
 * déclarés une seconde fois : ajouter un champ à un DetailsType suffit pour
 * qu'il soit proposé à l'habillage.
 */
final class AppFieldCatalog
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory,
        private readonly AppDetailsRegistry $registry,
    ) {
    }

    /**
     * @return list<AppField> dans l'ordre du formulaire
     */
    public function fieldsFor(AppKind $appKind): array
    {
        $form = $this->formFactory->create($this->registry->formTypeFor($appKind));
        $defaults = $this->registry->defaultsFor($appKind);
        $fields = [];

        foreach ($form->all() as $child) {
            $name = $child->getName();
            $config = $child->getConfig();
            $label = $config->getOption('label');
            $help = $config->getOption('help');

            $fields[] = new AppField(
                name: $name,
                label: \is_string($label) && '' !== $label ? $label : ucfirst($name),
                help: \is_string($help) ? $help : '',
                kind: AppFieldKind::fromFormType($config->getType()->getInnerType()),
                required: $config->getRequired(),
                default: $defaults[$name] ?? null,
                choices: self::choices($config->getOption('choices')),
            );
        }

        return $fields;
    }

    public function field(AppKind $appKind, string $name): ?AppField
    {
        foreach ($this->fieldsFor($appKind) as $field) {
            if ($field->name === $name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @return array<string, int|string>
     */
    private static function choices(mixed $option): array
    {
        if (!\is_array($option)) {
            return [];
        }

        $choices = [];

        foreach ($option as $label => $value) {
            if (\is_int($value) || \is_string($value)) {
                $choices[(string) $label] = $value;
            }
        }

        return $choices;
    }
}
