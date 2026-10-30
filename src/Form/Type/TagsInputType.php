<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * Words kept as tags: a list of strings, posted as one text where `separator`
 * joins them. Read back, each is trimmed, the empty and the repeated dropped.
 */
class TagsInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'separator' => ',',
            'empty_data' => '',
        ]);
        $resolver->setAllowedTypes('separator', ['string']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $separator = $options['separator'];

        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?array $tags): string => implode($separator.' ', $tags ?? []),
            static fn (?string $text): array => array_values(array_unique(array_filter(
                array_map('trim', explode($separator, (string) $text)),
                static fn (string $tag): bool => '' !== $tag
            ))),
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['separator'] = $options['separator'];
    }
}
