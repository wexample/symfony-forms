<?php

namespace Wexample\SymfonyForms\Form;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\Helpers\Helper\ClassHelper;
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
}
