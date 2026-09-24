<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A short code written in one cell per character: the one sent by e-mail to
 * confirm an address, a TOTP, a backup code. One value on submission, whatever
 * the number of cells.
 */
class OtpInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'length' => 6,
            'alphanumeric' => false,
            'auto_submit' => true,
        ]);

        $resolver->setAllowedTypes('length', 'int');
        $resolver->setAllowedTypes('alphanumeric', 'bool');
        $resolver->setAllowedTypes('auto_submit', 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['length'] = $options['length'];
        $view->vars['alphanumeric'] = $options['alphanumeric'];
        $view->vars['auto_submit'] = $options['auto_submit'];
    }
}
