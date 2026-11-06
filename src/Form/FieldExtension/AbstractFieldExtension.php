<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * An extension doing the common thing: it changes nothing of the form's
 * building, hands the definition its options as they are, and draws them as
 * the definition has them.
 */
abstract class AbstractFieldExtension implements FieldExtensionInterface
{
    public function getKinds(): ?array
    {
        return null;
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
    }

    public function prepareSubmit(FormInterface $parent, FormInterface $field, mixed $submitted, array $options): void
    {
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
    }

    public function buildDefinition(array $options, FormInterface $form): array
    {
        return $options;
    }

    public function buildDrawn(array $definition, FormInterface $form): array
    {
        return $definition;
    }

    /** The root form's entity id, when it holds one. */
    protected static function readRecordId(FormInterface $form): ?string
    {
        $data = $form->getRoot()->getData();
        $id = is_object($data) && method_exists($data, 'getId') ? $data->getId() : null;

        return null === $id ? null : (string) $id;
    }
}
