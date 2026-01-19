<?php

namespace Wexample\SymfonyForms\Form\Type;

use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class TextType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'form_text';
    }
}
