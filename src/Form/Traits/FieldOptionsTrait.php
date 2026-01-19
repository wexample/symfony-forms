<?php

namespace Wexample\SymfonyForms\Form\Traits;

use Wexample\Helpers\Class\Traits\HasSnakeShortClassNameClassTrait;

trait FieldOptionsTrait
{
    use HasSnakeShortClassNameClassTrait;

    protected static function getClassNameSuffix(): ?string
    {
        return 'Type';
    }

    public function getBlockPrefix(): string
    {
        return self::getSnakeShortClassName();
    }
}
