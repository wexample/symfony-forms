<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * Text written on several lines. `versions_url`: where its content's earlier
 * versions are read — the record's id as `__id__` —, a bar then around the
 * field to read one, compare it with the draft and take it back (the design
 * system's `field-versions`, in twig and in `dynamic-form` alike).
 */
class TextareaInputType extends \Symfony\Component\Form\AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return \Symfony\Component\Form\Extension\Core\Type\TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'textarea_input';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'rows' => null,
            'max_rows' => null,
            'auto_resize' => false,
            'versions_url' => null,
        ]);
        $resolver->setAllowedTypes('versions_url', ['null', 'string']);
        $resolver->setAllowedTypes('rows', ['null', 'int']);
        $resolver->setAllowedTypes('max_rows', ['null', 'int']);
        $resolver->setAllowedTypes('auto_resize', ['bool']);
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        $view->vars['rows'] = $options['rows'];
        $view->vars['max_rows'] = $options['max_rows'];
        $view->vars['auto_resize'] = $options['auto_resize'];
        $view->vars['versions_url'] = $options['versions_url'];
        // The address with the record's id in it, for the drawn field; the
        // placeholder kept in `versions_url` for a definition, which puts
        // the id there itself.
        $view->vars['versions_url_resolved'] = self::resolveVersionsUrl($options['versions_url'], $form);
    }

    private static function resolveVersionsUrl(?string $url, FormInterface $form): ?string
    {
        if (null === $url) {
            return null;
        }

        $data = $form->getRoot()->getData();
        $id = is_object($data) && method_exists($data, 'getId') ? $data->getId() : null;

        if (str_contains($url, '__id__')) {
            return null === $id ? null : str_replace('__id__', (string) $id, $url);
        }

        return $url;
    }
}
