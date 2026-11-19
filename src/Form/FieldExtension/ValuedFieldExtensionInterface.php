<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

/**
 * An extension holding a value of its own beside the field's — the name of
 * a variable the field is bound to —, never touching the field's: it takes
 * it as its `value` option, the definition hands it to the design system
 * with the rest, and it is sent back under `<field>__<extension>`
 * (FieldExtensions::key()). The form reads it there, out of the field's
 * transformers, and keeps it for whoever processes the form
 * (FieldExtensions::submitted()).
 */
interface ValuedFieldExtensionInterface extends FieldExtensionInterface
{
    /** The option the value comes in by, and goes back to the design system by. */
    public const string OPTION_VALUE = 'value';

    /**
     * What is wrong with a value sent — said on the field —, or null. An
     * empty value, sent as nothing, is null here.
     */
    public function validateValue(mixed $value, array $options): ?string;
}
