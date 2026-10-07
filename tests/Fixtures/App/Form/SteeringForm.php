<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\RadioInputType;
use Wexample\SymfonyForms\Form\Type\RangeInputType;
use Wexample\SymfonyForms\Form\Type\SwitchInputType;

/**
 * A bar steering a view rather than recording anything: a window among a few,
 * a coefficient along a scale, a switch. It holds no submit button, because
 * the form is sent as soon as one of the three settles on a value.
 */
class SteeringForm extends AbstractForm
{
    public const array WINDOWS = [
        '3 months' => '3',
        '6 months' => '6',
    ];

    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $common = [
            self::FIELD_OPTION_NAME_LABEL => true,
            self::FIELD_OPTION_NAME_REQUIRED => false,
        ];

        $builder->add('window', RadioInputType::class, $common + [
            'choices' => self::WINDOWS,
            'auto_translate_choices' => false,
            'segmented' => true,
        ]);

        $builder->add('coverage', RangeInputType::class, $common + [
            'min' => 0.5,
            'max' => 2,
            'step' => 0.05,
        ]);

        $builder->add('smoothPeaks', SwitchInputType::class, $common);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            // The labels are asserted as the keys they are built from, so the
            // tests do not depend on a translation catalogue.
            'translation_domain' => false,
            'csrf_protection' => false,
            'submit_on_change' => true,
        ]);
    }
}
