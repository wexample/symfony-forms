<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;
use Wexample\SymfonyForms\Helper\OptionsHelper;

/**
 * One choice among options searched rather than all given — a registry too
 * long to send whole —: a text, posted as it is. The same in the twig field
 * and the vue one (the design system's `search-select-input`), handed on by
 * `form_definition()` (`search_select_input.json`):
 *
 * - `options`: options the field holds itself — words, `value => label`, or
 *   `['value' => …, 'label' => …]`;
 * - `options_url`: a source asked for what is typed (`?q=`), answering words
 *   or `{ value, label }`;
 * - `value_label`: the label of the value given, where `options` do not
 *   hold it — else the source is asked for it.
 *
 * Not a choice type: it does not hold every option, so it cannot check the
 * value is one of them. That is the application's to check, by a constraint
 * of its own on the field. A field of an application is this type with its
 * options preset (`getParent()`, « A field standardised »).
 */
class SearchSelectInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'options' => [],
            'options_url' => null,
            'value_label' => null,
            'empty_data' => '',
        ]);
        $resolver->setAllowedTypes('options', ['array']);
        $resolver->setAllowedTypes('options_url', ['null', 'string']);
        $resolver->setAllowedTypes('value_label', ['null', 'string']);
        $resolver->setNormalizer('options', static fn (Options $options, array $list): array => OptionsHelper::normalize($list));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $value = (string) ($view->vars['value'] ?? '');

        $view->vars['options'] = $options['options'];
        $view->vars['options_url'] = $options['options_url'];
        $view->vars['value_label'] = $options['value_label'] ?? ('' !== $value ? OptionsHelper::labelOf($options['options'], $value) : null);
    }
}
