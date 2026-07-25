<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class EmailInputType extends EmailType
{
    use FieldOptionsTrait;
}
