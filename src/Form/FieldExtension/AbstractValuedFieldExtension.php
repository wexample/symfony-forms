<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A valued extension doing the common thing: its value an option, none by
 * default — handed to the design system as it is —, any value accepted.
 */
abstract class AbstractValuedFieldExtension extends AbstractFieldExtension implements ValuedFieldExtensionInterface
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION_VALUE, null);
    }

    public function validateValue(mixed $value, array $options): ?string
    {
        return null;
    }
}
