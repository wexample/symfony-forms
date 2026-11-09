<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * Several values of one kind, an item each, in their order: Symfony's
 * collection — `entry_type`, `entry_options` — drawn as the design system's
 * `collection-input`, the same in the twig field and the vue one, and handed
 * on by `form_definition()` (`collection_input.schema.json`).
 *
 * Items are added and taken off unless said (`allow_add`, `allow_delete`),
 * moved up and down; posted each under its index, renumbered in the order
 * shown. An item left empty is dropped (`delete_empty`).
 *
 * `template`: what an item added starts from — an example to fill in —,
 * the prototype's data unless `prototype_data` says otherwise.
 */
class CollectionInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_add' => true,
            'allow_delete' => true,
            'prototype' => true,
            'delete_empty' => true,
            'template' => null,
        ]);
        $resolver->setNormalizer(
            'prototype_data',
            static fn (Options $options, mixed $data): mixed => $data ?? $options['template'],
        );
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['template'] = $options['template'];
        // What the prototype holds in place of an item's index, for the
        // script to put the next one there.
        $view->vars['prototype_name'] = $options['prototype_name'];
    }
}
