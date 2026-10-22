<?php

namespace Wexample\SymfonyForms\Form\Type;

use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class PasswordInputType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\PasswordType::class;
    }
}
