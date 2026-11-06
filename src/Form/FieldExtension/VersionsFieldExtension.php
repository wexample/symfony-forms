<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Service\FormDefinitionBuilder;

/**
 * The earlier versions of what a field holds, read at `url` — the record's
 * id where the form's action has it (`__id__`) —, answering
 * `[{ label, content, date, author }]`, the latest first, without the current
 * one. The design system draws them as the field's footer: read, compared
 * with the draft, taken back without saving.
 */
class VersionsFieldExtension extends AbstractFieldExtension
{
    public static function getName(): string
    {
        return 'versions';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('url');
        $resolver->setAllowedTypes('url', 'string');
    }

    /** A drawn field knows its record: its id in place of the placeholder; none, no versions to read. */
    public function buildDrawn(array $definition, FormInterface $form): array
    {
        if (! str_contains($definition['url'], FormDefinitionBuilder::ID_PLACEHOLDER)) {
            return $definition;
        }

        $id = self::readRecordId($form);

        return null === $id ? [] : ['url' => str_replace(FormDefinitionBuilder::ID_PLACEHOLDER, $id, $definition['url'])];
    }
}
