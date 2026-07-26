<?php

namespace Wexample\SymfonyForms\Form\Type;

class ButtonInputType extends SubmitInputType
{
    public function getBlockPrefix(): string
    {
        return 'button_input';
    }
}
