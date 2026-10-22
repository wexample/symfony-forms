<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class TextareaInputType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'textarea_input';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'rows' => null,
            'max_rows' => null,
            'auto_resize' => false,
        ]);
        $resolver->setAllowedTypes('rows', ['null', 'int']);
        $resolver->setAllowedTypes('max_rows', ['null', 'int']);
        $resolver->setAllowedTypes('auto_resize', ['bool']);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $view->vars['rows'] = $options['rows'];
        $view->vars['max_rows'] = $options['max_rows'];
        $view->vars['auto_resize'] = $options['auto_resize'];
    }
}
