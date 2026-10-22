<?php

namespace Wexample\SymfonyForms\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Type\FloatType;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;

/**
 * A fixed word beside the value — a unit, a currency — which the design-system
 * text and number inputs draw inside the field's frame and announce with it.
 *
 * Only the types rendered through those two components take it. The addon is
 * printed, never submitted: the value stays the whole of the data, so `72,5`
 * with a `kg` suffix is stored as 72.5 and not as a string to parse.
 */
class InputAddonTypeExtension extends AbstractTypeExtension
{
    public const string OPTION_PREFIX = 'prefix';
    public const string OPTION_SUFFIX = 'suffix';

    public static function getExtendedTypes(): iterable
    {
        return [
            TextInputType::class,
            NumberInputType::class,
            FloatType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPTION_PREFIX => null,
            self::OPTION_SUFFIX => null,
        ]);

        $resolver->setAllowedTypes(self::OPTION_PREFIX, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPTION_SUFFIX, ['null', 'string']);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $view->vars[self::OPTION_PREFIX] = $options[self::OPTION_PREFIX];
        $view->vars[self::OPTION_SUFFIX] = $options[self::OPTION_SUFFIX];
    }
}
