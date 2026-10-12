<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A number, and on demand (`stepper`) a button on each side to take one step
 * down or up — a quantity. Stepped, it is the browser's number field, which
 * knows how to step and where to stop: its `min` and `max` are read from the
 * field's Range constraint unless set.
 */
class NumberInputType extends NumberType
{
    use FieldOptionsTrait;

    public const string OPTION_STEPPER = 'stepper';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefault(self::OPTION_STEPPER, false);
        $resolver->setAllowedTypes(self::OPTION_STEPPER, 'bool');
        $resolver->setNormalizer('html5', static fn (Options $options, $html5) => $html5 || $options[self::OPTION_STEPPER]);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        parent::buildView($view, $form, $options);

        $view->vars[self::OPTION_STEPPER] = $options[self::OPTION_STEPPER];

        if (! $options[self::OPTION_STEPPER]) {
            return;
        }

        foreach ($options['constraints'] as $constraint) {
            if (! $constraint instanceof Range) {
                continue;
            }

            if (null !== $constraint->min && ! isset($view->vars['attr']['min'])) {
                $view->vars['attr']['min'] = $constraint->min;
            }

            if (null !== $constraint->max && ! isset($view->vars['attr']['max'])) {
                $view->vars['attr']['max'] = $constraint->max;
            }
        }
    }
}
