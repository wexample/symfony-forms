<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Wexample\SymfonyForms\Form\AbstractType;

class FloatType extends AbstractType
{
    public function getParent(): string
    {
        return NumberType::class;
    }
}
