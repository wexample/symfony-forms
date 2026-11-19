<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Whether the field is something more — entered in a test matrix, among
 * others —: a yes or no of its own beside the field's value, which it never
 * touches (ValuedFieldExtensionInterface). The design system draws it as its
 * glyph alone in the strip under the field (`components/form/extension/toggle`):
 * lit while on, its `label` under the pointer, pressed to switch.
 *
 *   'extensions' => ['toggle' => ['value' => true, 'icon' => 'ph:bold/flask', 'label' => 'In the test matrix']]
 *
 * Read back: FieldExtensions::submitted($form, 'model', 'toggle'), a bool.
 */
class ToggleFieldExtension extends AbstractValuedFieldExtension
{
    public static function getName(): string
    {
        return 'toggle';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault(self::OPTION_VALUE, false);
        $resolver->setAllowedTypes(self::OPTION_VALUE, 'bool');
        $resolver->setRequired(['icon', 'label']);
        $resolver->setAllowedTypes('icon', 'string');
        $resolver->setAllowedTypes('label', 'string');
    }

    /** « 1 », « true », « on » on; anything else off. */
    public function readValue(mixed $sent, array $options): mixed
    {
        return filter_var($sent, FILTER_VALIDATE_BOOL);
    }
}
