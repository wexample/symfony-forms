<?php

namespace Wexample\SymfonyForms\Form\Type;

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
}
