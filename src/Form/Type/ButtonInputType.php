<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ButtonType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class ButtonInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return ButtonType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'icon' => null,
        ]);
        $resolver->setAllowedTypes('icon', ['null', 'string']);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $view->vars['icon'] = $options['icon'];
    }
}
