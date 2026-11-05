<?php

namespace Wexample\SymfonyForms\Helper;

/**
 * Options a field offers — the words of a tags field, the choices of a
 * searched select —, read the same way whatever shape a form gives them, as
 * the design system's OptionsHelper reads what a source answers.
 */
class OptionsHelper
{
    /**
     * A list of words, a list of `['value' => …, 'label' => …]`, or
     * `value => label`: each value once, trimmed, its label the value where
     * none is said.
     *
     * @return list<array{value: string, label: string}>
     */
    public static function normalize(array $options): array
    {
        $normalized = [];

        foreach ($options as $key => $option) {
            if (is_array($option)) {
                $value = (string) ($option['value'] ?? '');
                $label = (string) ($option['label'] ?? $value);
            } elseif (is_string($key)) {
                $value = $key;
                $label = (string) $option;
            } else {
                $value = (string) $option;
                $label = $value;
            }

            $value = trim($value);

            if ('' !== $value && ! isset($normalized[$value])) {
                $normalized[$value] = ['value' => $value, 'label' => '' !== trim($label) ? trim($label) : $value];
            }
        }

        return array_values($normalized);
    }

    /**
     * The label of a value among options, null where none holds it.
     *
     * @param list<array{value: string, label: string}> $options
     */
    public static function labelOf(array $options, string $value): ?string
    {
        foreach ($options as $option) {
            if ($option['value'] === $value) {
                return $option['label'];
            }
        }

        return null;
    }
}
