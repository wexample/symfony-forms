<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * Words kept as tags: a list of strings, posted as one text where `separator`
 * joins them. Read back, each is trimmed, the empty and the repeated dropped.
 *
 * What the field offers and how it draws its tags, the same in the twig field
 * and the vue one (the design system's `tags-input`), and handed on by
 * `form_definition()` (`tags_input.json`):
 *
 * - `suggestions`: words offered while typing — a list of words, a list of
 *   `['value' => …, 'label' => …]`, or `value => label`;
 * - `suggestions_url`: a source asked for what is typed (`?q=`), answering
 *   words or `{ value, label }`;
 * - `tone`, `glyph`: the marker every tag wears — a state's tone or a
 *   category's (`cat-straw`), and an icon before its word.
 *
 * A field of an application — stamps, labels — is this type with its options
 * preset: a type whose `getParent()` is this one (see the knowledge,
 * « A field standardised »).
 */
class TagsInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'separator' => ',',
            'empty_data' => '',
            'suggestions' => [],
            'suggestions_url' => null,
            'tone' => 'neutral',
            'glyph' => null,
        ]);
        $resolver->setAllowedTypes('separator', ['string']);
        $resolver->setAllowedTypes('suggestions', ['array']);
        $resolver->setAllowedTypes('suggestions_url', ['null', 'string']);
        $resolver->setAllowedTypes('tone', ['string']);
        $resolver->setAllowedTypes('glyph', ['null', 'string']);
        $resolver->setNormalizer('suggestions', static fn (Options $options, array $suggestions): array => self::normalizeSuggestions($suggestions));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $separator = $options['separator'];

        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?array $tags): string => implode($separator.' ', $tags ?? []),
            static fn (?string $text): array => self::split((string) $text, $separator),
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['separator'] = $options['separator'];
        $view->vars['tags'] = self::split((string) $view->vars['value'], $options['separator']);
        $view->vars['suggestions'] = $options['suggestions'];
        $view->vars['suggestions_url'] = $options['suggestions_url'];
        $view->vars['tone'] = $options['tone'];
        $view->vars['glyph'] = $options['glyph'];
    }

    /**
     * @return list<string>
     */
    public static function split(string $text, string $separator): array
    {
        return array_values(array_unique(array_filter(
            array_map('trim', explode($separator, $text)),
            static fn (string $tag): bool => '' !== $tag
        )));
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function normalizeSuggestions(array $suggestions): array
    {
        $normalized = [];

        foreach ($suggestions as $key => $suggestion) {
            if (is_array($suggestion)) {
                $value = (string) ($suggestion['value'] ?? '');
                $label = (string) ($suggestion['label'] ?? $value);
            } elseif (is_string($key)) {
                $value = $key;
                $label = (string) $suggestion;
            } else {
                $value = (string) $suggestion;
                $label = $value;
            }

            $value = trim($value);

            if ('' !== $value && ! isset($normalized[$value])) {
                $normalized[$value] = ['value' => $value, 'label' => '' !== trim($label) ? trim($label) : $value];
            }
        }

        return array_values($normalized);
    }
}
