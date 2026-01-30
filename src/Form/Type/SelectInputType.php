<?php

namespace Wexample\SymfonyForms\Form\Type;

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
}
