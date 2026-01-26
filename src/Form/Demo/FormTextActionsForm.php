<?php

namespace Wexample\SymfonyForms\Form\Demo;

use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\TextInputType;

class FormTextActionsForm extends AbstractForm
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    )
    {
        $builder
            ->add(
                'name',
                TextInputType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                    self::FIELD_OPTION_NAME_REQUIRED => false,
                    self::FIELD_OPTION_NAME_MAPPED => false,
                ]
            )
            ->add(
                'submit_primary',
                SubmitType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                ]
            )
            ->add(
                'submit_secondary',
                SubmitType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                ]
            )
            ->add(
                'submit_tertiary',
                SubmitType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                ]
            );
    }
}
