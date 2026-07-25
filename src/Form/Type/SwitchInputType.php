<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class SwitchInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}
