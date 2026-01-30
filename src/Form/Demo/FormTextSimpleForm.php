<?php

namespace Wexample\SymfonyForms\Form\Demo;

use Symfony\Component\Form\FormBuilderInterface;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\SelectInputType;
use Wexample\SymfonyForms\Form\Type\TextareaInputType;
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
            );

        $builder
            ->add(
                'text_select',
                SelectInputType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                    self::FIELD_OPTION_NAME_REQUIRED => false,
                    self::FIELD_OPTION_NAME_MAPPED => false,
                    'choices' => [
                        'First choice' => 'first',
                        'Second choice' => 'second',
                        'Group' => [
                            'Third choice' => 'third',
                        ],
                    ],
                    'placeholder' => true,
                ]
            )
            ->add(
                'text_area',
                TextareaInputType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => true,
                    self::FIELD_OPTION_NAME_REQUIRED => false,
                    self::FIELD_OPTION_NAME_MAPPED => false,
                    'rows' => 4,
                    'max_rows' => 12,
                    'auto_resize' => true,
                    'attr' => [
                        'placeholder' => true,
                    ],
                ]
            );

        $this->builderAddSubmit($builder);
    }
}
