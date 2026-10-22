<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A value shown among the fields of a form without being one of them.
 *
 * What it is for: a record reads as one block — the dates the application
 * writes itself next to the attributes someone typed — instead of splitting
 * into a form and a summary beside it.
 *
 * It is not a read-only field, and the difference is the point. A read-only
 * field is bound to the model and submitted; this renders no element a browser
 * submits, and `mapped` is false, so there is nothing to forge and nothing to
 * ignore. `disabled` says the same thing a second time, on the chance the
 * widget is ever given a `name`.
 *
 * The value arrives formatted: a date in the reader's timezone, a number in
 * their locale. Formatting belongs to whoever knows the reader, which is the
 * application, not the field.
 */
class DisplayValueType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'disabled' => true,
        ]);
    }
}
