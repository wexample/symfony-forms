<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class FileInputType extends FileType
{
    use FieldOptionsTrait;
}
