<?php

namespace Wexample\SymfonyForms\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyForms\Form\Type\SubmitInputType;
use Wexample\SymfonyTranslations\Translation\Translator;

class AbstractForm extends \Symfony\Component\Form\AbstractType
{
    public static bool $ajax = false;

    public const FIELD_OPTION_NAME_LABEL = 'label';
    public const FIELD_OPTION_NAME_REQUIRED = 'required';
    public const FIELD_OPTION_NAME_MAPPED = 'mapped';

    public static function transForm(
        string $key,
        FormInterface $form
    ): string {
        return self::transFormDomain($form)
            . Translator::DOMAIN_SEPARATOR
            . $key;
    }

    public static function transFormDomain(
        FormInterface $form
    ): string {
        return self::transTypeDomain(
            $form
                ->getRoot()
                ->getConfig()
                ->getType()
                ->getInnerType()
        );
    }

    public static function transTypeDomain(
        object|string $type
    ): string {
        return 'front.forms.' . ClassHelper::longTableized($type, '.');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => self::transTypeDomain($this),
        ]);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $view->vars['ajax'] = static::$ajax;
    }

    protected function builderAddSubmit(
        FormBuilderInterface $builder,
        string $label = 'action.submit',
        array $options = []
    ): void {
        $builder
            ->add(
                'submit',
                SubmitInputType::class,
                array_merge(
                    [
                        self::FIELD_OPTION_NAME_LABEL => $label,
                    ],
                    $options
                )
            );
    }
}
