<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The field bound to a variable, by its name — a test variable a matrix
 * gives values to, the field keeping its own value outside it —: a name of
 * its own beside the field's value, which it never touches
 * (ValuedFieldExtensionInterface). The design system draws it in the strip
 * under the field (`components/form/extension/variable`): its icon, lit
 * while bound, the name shown; pressed, the name written; emptied, unbound.
 *
 *   'extensions' => ['variable' => ['value' => 'model', 'icon' => 'ph:bold/flask', 'suggestions' => ['model', 'temperature']]]
 *
 * Read back: FieldExtensions::submitted($form, 'model', 'variable'), the
 * name or null. A name not matching `pattern` is refused on the field.
 */
class VariableFieldExtension extends AbstractValuedFieldExtension
{
    /** One word, a letter first. */
    public const string PATTERN = '^[A-Za-z][A-Za-z0-9_]{0,63}$';

    public static function getName(): string
    {
        return 'variable';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setAllowedTypes(self::OPTION_VALUE, ['null', 'string']);
        $resolver->setDefaults([
            'pattern' => self::PATTERN,
            'icon' => 'ph:bold/brackets-curly',
            'suggestions' => [],
        ]);
        $resolver->setDefined('label');
        $resolver->setAllowedTypes('pattern', 'string');
        $resolver->setAllowedTypes('icon', 'string');
        $resolver->setAllowedTypes('label', 'string');
        $resolver->setAllowedTypes('suggestions', 'string[]');
        $resolver->setNormalizer('suggestions', static fn ($options, array $suggestions): array => array_values(array_unique($suggestions)));
    }

    public function validateValue(mixed $value, array $options): ?string
    {
        if (null === $value) {
            return null;
        }

        return is_string($value) && preg_match('/'.str_replace('/', '\\/', $options['pattern']).'/', $value) === 1
            ? null
            : sprintf('"%s" is no variable name: one word, a letter first.', is_scalar($value) ? $value : '');
    }
}
