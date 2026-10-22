<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Extension\InputAddonTypeExtension;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A value taken along a scale — a coefficient, a buffer —, drawn by the
 * design system's range input. A number: what is submitted is read with a
 * point whatever the locale, as a range input writes it (`html5`), and the
 * data is a figure, not a string.
 */
class RangeInputType extends NumberType
{
    use FieldOptionsTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'html5' => true,
            'min' => 0,
            'max' => 100,
            'step' => 1,
            // A word after the value shown — a unit —, never submitted.
            InputAddonTypeExtension::OPTION_SUFFIX => null,
        ]);

        $resolver->setAllowedTypes('min', ['int', 'float']);
        $resolver->setAllowedTypes('max', ['int', 'float']);
        $resolver->setAllowedTypes('step', ['int', 'float']);
        $resolver->setAllowedTypes(InputAddonTypeExtension::OPTION_SUFFIX, ['null', 'string']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['min'] = $options['min'];
        $view->vars['max'] = $options['max'];
        $view->vars['step'] = $options['step'];
        $view->vars[InputAddonTypeExtension::OPTION_SUFFIX] = $options[InputAddonTypeExtension::OPTION_SUFFIX];
    }
}
