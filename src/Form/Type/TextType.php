<?php

namespace Wexample\SymfonyForms\Form\Type;

use Wexample\SymfonyForms\Form\AbstractType;

class TextType extends AbstractType
{
    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextType::class;
    }
}
