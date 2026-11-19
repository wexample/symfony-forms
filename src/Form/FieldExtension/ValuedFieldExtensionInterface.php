<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

/**
 * An extension holding a value of its own beside the field's — whether the
 * field enters a test matrix —, never touching the field's: it takes
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
     * The value as the extension holds it, from what was sent: a text, as a
     * form posts — « 1 », « 0 » for a toggle.
     */
    public function readValue(mixed $sent, array $options): mixed;

    /** What is wrong with a value read — said on the field —, or null. */
    public function validateValue(mixed $value, array $options): ?string;
}
