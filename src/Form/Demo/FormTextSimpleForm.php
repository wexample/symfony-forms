<?php

namespace Wexample\SymfonyForms\Form\Demo;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
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
                    self::FIELD_OPTION_NAME_LABEL => 'Text',
                    self::FIELD_OPTION_NAME_REQUIRED => false,
                    self::FIELD_OPTION_NAME_MAPPED => false,
                ]
            )
            ->add(
                'submit',
                SubmitInputType::class,
                [
                    self::FIELD_OPTION_NAME_LABEL => 'Submit',
                    'icon' => 'ph:bold/paper-plane-tilt',
                ]
            );

        $builder->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $form = $event->getForm();
                $form->addError(new FormError('Example form error'));

                if ($form->has('text_simple')) {
                    $form->get('text_simple')->addError(new FormError('Example field error'));
                }
            }
        );
    }
}
