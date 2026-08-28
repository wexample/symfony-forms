<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class DateInputType extends DateType
{
    use FieldOptionsTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => true,
        ]);
    }
}
