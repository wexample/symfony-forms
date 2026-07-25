<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class MonthInputType extends TextType
{
    use FieldOptionsTrait;
}
