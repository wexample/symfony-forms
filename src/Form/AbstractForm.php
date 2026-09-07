<?php

namespace Wexample\SymfonyForms\Form;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\Helpers\Helper\TextHelper;
use Wexample\SymfonyForms\Form\Type\SubmitInputType;
use Wexample\SymfonyLoader\Helper\LoaderHelper;
use Wexample\SymfonyTranslations\Translation\Translator;

class AbstractForm extends \Symfony\Component\Form\AbstractType
{
    public static bool $ajax = false;

    public const FIELD_OPTION_NAME_LABEL = 'label';
    public const FIELD_OPTION_NAME_REQUIRED = 'required';
    public const FIELD_OPTION_NAME_MAPPED = 'mapped';

    private const string FORM_NAMESPACE_SEGMENT
        = ClassHelper::PATH_SEPARATOR
        . ClassHelper::CLASS_PATH_PART_FORM
        . ClassHelper::PATH_SEPARATOR;

    private const string NAMESPACE_ROOT_APPLICATION = ClassHelper::CLASS_PATH_PART_APP;

    private const string DOMAIN_ROOT_APPLICATION = LoaderHelper::TWIG_NAMESPACE_FRONT;

    private const string DOMAIN_FORMS_SEGMENT
        = Translator::KEYS_SEPARATOR
        . 'forms'
        . Translator::KEYS_SEPARATOR;

    private const string BUNDLE_CLASS_SUFFIX = 'Bundle';

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
        $config = $form->getRoot()->getConfig();

        return $config->getOption('translation_domain')
            ?? self::transTypeDomain($config->getType()->getInnerType());
    }

    /**
     * The translation domain a form type reads its labels from.
     *
     * It restates where the file sits: what follows `Form\` in the class name
     * is what follows `assets/forms/` on disk. What precedes it says whose
     * assets those are — `front` for the application, the bundle's own alias
     * otherwise, which is how the translator keys the two apart.
     */
    public static function transTypeDomain(
        object|string $type
    ): string {
        $class = ClassHelper::getRealClassPath($type);
        $position = strpos($class, self::FORM_NAMESPACE_SEGMENT);

        if ($position === false) {
            return self::DOMAIN_ROOT_APPLICATION
                . self::DOMAIN_FORMS_SEGMENT
                . TextHelper::toSnake(ClassHelper::getShortName($class));
        }

        $root = substr($class, 0, $position);
        $under = substr($class, $position + strlen(self::FORM_NAMESPACE_SEGMENT));

        return self::transDomainRoot($root)
            . self::DOMAIN_FORMS_SEGMENT
            . implode(
                Translator::KEYS_SEPARATOR,
                array_map(
                    TextHelper::class . '::toSnake',
                    explode(ClassHelper::PATH_SEPARATOR, $under)
                )
            );
    }

    /**
     * Whose assets a class living under this namespace root belongs to.
     *
     * `symfony-loader` registers a bundle's translations under `@` plus its
     * alias, and Symfony builds that alias from the bundle class name — itself
     * the namespace root, flattened, plus `Bundle`.
     */
    private static function transDomainRoot(string $namespaceRoot): string
    {
        if ($namespaceRoot === self::NAMESPACE_ROOT_APPLICATION) {
            return self::DOMAIN_ROOT_APPLICATION;
        }

        return str_replace(ClassHelper::PATH_SEPARATOR, '', $namespaceRoot)
            . self::BUNDLE_CLASS_SUFFIX;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'translation_domain' => self::transTypeDomain($this),
            'required_mode' => 'optional',
        ]);
        $resolver->setAllowedValues('required_mode', ['asterisk', 'optional', false]);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $view->vars['ajax'] = static::$ajax;
        $view->vars['required_mode'] = $options['required_mode'];
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
                        'primary' => true,
                    ],
                    $options
                )
            );
    }
}
