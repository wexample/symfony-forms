<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * What is attached to a field whatever its kind — its earlier versions, an
 * expression in place of its value —, declared once and set on any field by
 * the one option every type takes (`extensions`, FieldExtensionsTypeExtension):
 *
 *   'extensions' => ['versions' => ['url' => '/_api/template/versions/__id__']]
 *
 * An extension names itself, says what it takes, and hands the form and the
 * view what they need. It is a tagged service: an application declares its
 * own the same way, and the design system draws it by its name
 * (`components/form/extension/<name>`), the norm checks it
 * (`extension_<name>.schema.json`).
 */
interface FieldExtensionInterface
{
    /** The name the field, the norm and the design system know it by. */
    public static function getName(): string;

    /** What it takes, as the norm's `extension_<name>.schema.json` says. */
    public function configureOptions(OptionsResolver $resolver): void;

    /** The kinds of field it goes on; null for any. */
    public function getKinds(): ?array;

    /** The field's building, when the extension changes how it reads what is sent. */
    public function buildForm(FormBuilderInterface $builder, array $options): void;

    /**
     * The parent form about to submit the field, what is sent for it in hand:
     * when the extension changes how the field reads it — another type put
     * in its place —, here, before the field is given it.
     */
    public function prepareSubmit(FormInterface $parent, FormInterface $field, mixed $submitted, array $options): void;

    /** The field's view, when the extension changes what the drawn field shows. */
    public function buildView(FormView $view, FormInterface $form, array $options): void;

    /**
     * What the definition carries for it (`form_definition()`): the shape of
     * the norm, the entity's id left as the action's placeholder.
     */
    public function buildDefinition(array $options, FormInterface $form): array;

    /**
     * What the drawn field's component is given: the definition's shape, with
     * what only a drawn field knows — the record's id in place of the placeholder.
     */
    public function buildDrawn(array $definition, FormInterface $form): array;
}
