<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Type\TextareaInputType;

/**
 * A field shaped as symfony-coding's CodeInputType hands itself to the view —
 * the same prefix, the same vars —, so the definition is checked here without
 * the package that draws it.
 */
class CodeLikeType extends AbstractType
{
    public function getParent(): string
    {
        return TextareaInputType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'code_input';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['language' => null, 'language_field' => null, 'language_map' => []]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['language'] = $options['language'];
        $view->vars['language_map'] = $options['language_map'];
        $view->vars['language_source'] = $options['language_field'] && $view->parent
            ? $view->parent->vars['id'].'_'.$options['language_field']
            : null;
    }
}
