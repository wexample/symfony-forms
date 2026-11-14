<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Service\FormDefinitionBuilder;

/**
 * What a field's value designates, opened from the field: a button at the end
 * of its frame, there only while the field holds a value, leading to `url` —
 * the value where it says `__value__`, the record's id where it says `__id__`
 * — in a new tab, a modal or a panel (`in`; not `target`, which the design
 * system gives every extension of a page's field). The design system draws it
 * (`components/form/extension/open`).
 */
class OpenFieldExtension extends AbstractFieldExtension
{
    public const string VALUE_PLACEHOLDER = '__value__';

    public const string TARGET_TAB = 'tab';

    public const string TARGET_MODAL = 'modal';

    public const string TARGET_PANEL = 'panel';

    public static function getName(): string
    {
        return 'open';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('url');
        $resolver->setAllowedTypes('url', 'string');
        $resolver->setDefault('in', self::TARGET_TAB);
        $resolver->setAllowedValues('in', [self::TARGET_TAB, self::TARGET_MODAL, self::TARGET_PANEL]);
        $resolver->setDefined('label');
        $resolver->setAllowedTypes('label', 'string');
    }

    /** A drawn field knows its record: its id in place of the placeholder, when it has one. */
    public function buildDrawn(array $definition, FormInterface $form): array
    {
        $id = self::readRecordId($form);

        if (null !== $id && str_contains($definition['url'], FormDefinitionBuilder::ID_PLACEHOLDER)) {
            $definition['url'] = str_replace(FormDefinitionBuilder::ID_PLACEHOLDER, $id, $definition['url']);
        }

        return $definition;
    }
}
