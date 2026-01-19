<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class FloatType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return NumberType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'form_float';
    }
}
