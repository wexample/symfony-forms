<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TelType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A phone number, typed `tel`: a phone shows its keypad. Its format is the
 * application's to check (a `Regex` constraint), there is no one rule.
 */
class TelInputType extends TelType
{
    use FieldOptionsTrait;
}
