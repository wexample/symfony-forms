<?php

namespace Wexample\SymfonyForms\Form\Demo;

use Symfony\Component\Form\FormBuilderInterface;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\SubmitInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;

class FormTextSimpleForm extends AbstractForm
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ) {
        $builder
            ->add(
                'text_simple',
                TextInputType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                    self::FIELD_OPTION_NAME_REQUIRED => false,
                    self::FIELD_OPTION_NAME_MAPPED => false,
                ]
            )
            ->add(
                'submit',
                SubmitInputType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                    'icon' => 'ph:bold/paper-plane-tilt',
                ]
            );
    }
}
