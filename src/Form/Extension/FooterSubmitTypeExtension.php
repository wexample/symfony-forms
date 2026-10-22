<?php

namespace Wexample\SymfonyForms\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * `in_footer`: a submit button drawn out of the form's body, at the foot of
 * the modal, panel or page holding it — where `form_footer(form)` writes it,
 * in the page's `page_actions` block. It still sends the form, through the
 * button's `form` attribute.
 */
class FooterSubmitTypeExtension extends AbstractTypeExtension
{
    public const string OPTION_IN_FOOTER = 'in_footer';

    public static function getExtendedTypes(): iterable
    {
        return [SubmitType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION_IN_FOOTER, false);
        $resolver->setAllowedTypes(self::OPTION_IN_FOOTER, 'bool');
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars[self::OPTION_IN_FOOTER] = $options[self::OPTION_IN_FOOTER];
    }
}
