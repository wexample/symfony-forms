<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A box ticked or not. `SwitchInputType` holds the same value drawn as a
 * switch: the application picks the one that reads best.
 */
class CheckboxInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return CheckboxType::class;
    }
}
