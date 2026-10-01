<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class SelectInputType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\ChoiceType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'select_input';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('auto_translate_choices', true);
        $resolver->setNormalizer('choices', function (Options $options, $choices) {
            // A list of scalars is keyed by itself, so each value is also its
            // label key. A list holding objects (entities, enums) is left to
            // ChoiceType, which reads it with `choice_value` / `choice_label`.
            if (! is_array($choices) || ! array_is_list($choices)) {
                return $choices;
            }

            foreach ($choices as $choice) {
                if (! is_scalar($choice)) {
                    return $choices;
                }
            }

            $map = [];
            foreach ($choices as $choice) {
                $map[$choice] = $choice;
            }

            return $map;
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        if (empty($options['auto_translate_choices'])) {
            return;
        }

        $fieldName = $form->getName();
        $prefix = '@form::field.' . $fieldName . '.choice.';

        $applyLabel = static function (ChoiceView $choice) use ($prefix): void {
            $choice->label = $prefix . $choice->value . '.label';
        };

        foreach ($view->vars['choices'] ?? [] as $choice) {
            if ($choice instanceof ChoiceGroupView) {
                foreach ($choice->choices as $groupChoice) {
                    if ($groupChoice instanceof ChoiceView) {
                        $applyLabel($groupChoice);
                    }
                }

                continue;
            }

            if ($choice instanceof ChoiceView) {
                $applyLabel($choice);
            }
        }
    }
}
