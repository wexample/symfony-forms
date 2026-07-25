<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class UrlInputType extends UrlType
{
    use FieldOptionsTrait;
}
