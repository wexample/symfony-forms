<?php

namespace Wexample\SymfonyForms\Form;

abstract class AbstractType extends \Symfony\Component\Form\AbstractType
{
    public const FIELD_OPTION_NAME_LABEL = 'label';
    public const FIELD_OPTION_NAME_REQUIRED = 'required';
    public const FIELD_OPTION_NAME_MAPPED = 'mapped';
}
