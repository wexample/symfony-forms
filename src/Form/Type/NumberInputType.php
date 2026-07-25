<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class NumberInputType extends NumberType
{
    use FieldOptionsTrait;
}
