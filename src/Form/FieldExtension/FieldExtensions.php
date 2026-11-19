<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\Form\FormInterface;

/**
 * The values of a form's valued extensions (ValuedFieldExtensionInterface),
 * as the form was given them: read by whoever processes it.
 *
 *   $variable = FieldExtensions::submitted($form, 'model', 'variable');
 *
 * What was not sent — the extension not drawn — is the value the field gave
 * it: nothing changed.
 */
final class FieldExtensions
{
    /** @var \WeakMap<FormInterface, array<string, array<string, mixed>>>|null By form, by field, by extension. */
    private static ?\WeakMap $values = null;

    /** The name a valued extension's value is sent under, beside its field's. */
    public static function key(string $field, string $extension): string
    {
        return $field.'__'.$extension;
    }

    /** The value sent for `field`'s `extension`, in `form` (the field's parent). */
    public static function submitted(FormInterface $form, string $field, string $extension): mixed
    {
        $kept = self::store()[$form] ?? [];

        if (array_key_exists($field, $kept) && array_key_exists($extension, $kept[$field])) {
            return $kept[$field][$extension];
        }

        // Never submitted, or not by this form: the field's own option.
        $config = $form->has($field) ? $form->get($field)->getConfig()->getOption('extensions', []) : [];

        return $config[$extension][ValuedFieldExtensionInterface::OPTION_VALUE] ?? null;
    }

    /** @internal Kept by FieldExtensionsTypeExtension as the form is submitted. */
    public static function keep(FormInterface $form, string $field, string $extension, mixed $value): void
    {
        $store = self::store();
        $kept = $store[$form] ?? [];
        $kept[$field][$extension] = $value;
        $store[$form] = $kept;
    }

    private static function store(): \WeakMap
    {
        return self::$values ??= new \WeakMap();
    }
}
