<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A valued extension doing the common thing: its value an option, none by
 * default — handed to the design system as it is —, read as sent — an
 * empty text, none —, any value accepted.
 */
abstract class AbstractValuedFieldExtension extends AbstractFieldExtension implements ValuedFieldExtensionInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION_VALUE, null);
    }

    public function readValue(mixed $sent, array $options): mixed
    {
        return '' === $sent ? null : $sent;
    }

    public function validateValue(mixed $value, array $options): ?string
    {
        return null;
    }
}
