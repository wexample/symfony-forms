<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A colour, held as `#rrggbb` (lowercase): picked, typed as its hex — `#abc`
 * and `ABC` read as `#aabbcc` —, or taken among the colours the field offers
 * (`swatches`: `#hex`, `#hex => label`, or `['value' => …, 'label' => …]`).
 * Drawn by the design system's `color-input`, handed on by
 * `form_definition()` (`color_input.schema.json`). What is not a colour is
 * refused with the field's `invalid_message`; nothing typed is `null`.
 */
class ColorInputType extends AbstractType
{
    use FieldOptionsTrait;

    private const string PATTERN = '/^#?([0-9a-f]{3}|[0-9a-f]{6})$/i';

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'swatches' => [],
            'invalid_message' => 'This is not a colour: write it as #rrggbb.',
        ]);
        $resolver->setAllowedTypes('swatches', ['array']);
        $resolver->setNormalizer('swatches', static fn (Options $options, array $swatches): array => self::normalizeSwatches($swatches));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?string $color): string => $color ?? '',
            static function (?string $text): ?string {
                $color = self::normalize($text);

                if (false === $color) {
                    throw new TransformationFailedException('Not a colour.');
                }

                return $color;
            },
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['swatches'] = $options['swatches'];
    }

    /**
     * `#aabbcc` for a colour, null for nothing, false for what is not one.
     */
    public static function normalize(?string $text): string|null|false
    {
        $text = trim((string) $text);

        if ('' === $text) {
            return null;
        }

        if (! preg_match(self::PATTERN, $text, $match)) {
            return false;
        }

        $hex = strtolower($match[1]);

        return '#'.(3 === strlen($hex) ? $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2] : $hex);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function normalizeSwatches(array $swatches): array
    {
        $normalized = [];

        foreach ($swatches as $key => $swatch) {
            $value = is_array($swatch) ? ($swatch['value'] ?? '') : (is_string($key) ? $key : $swatch);
            $label = is_array($swatch) ? ($swatch['label'] ?? null) : (is_string($key) ? $swatch : null);
            $color = self::normalize((string) $value);

            if (is_string($color) && ! isset($normalized[$color])) {
                $normalized[$color] = ['value' => $color, 'label' => (string) ($label ?? $color)];
            }
        }

        return array_values($normalized);
    }
}
