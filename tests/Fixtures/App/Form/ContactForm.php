<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Form\Type\CheckboxInputType;
use Wexample\SymfonyForms\Form\Type\TelInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Contact;

class ContactForm extends AbstractForm
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextInputType::class)
            ->add('phone', TelInputType::class, ['required' => false])
            ->add('newsletter', CheckboxInputType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults(['data_class' => Contact::class]);
    }
}
