<?php

namespace Wexample\SymfonyForms\Service;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A form handed to a vue rather than drawn (`form_props()`): the form as the
 * json-schema norm describes one (`form.json` — its fields each of the kind
 * the norm names, where and how it is sent), so the vue draws it from that
 * description alone, as it would draw one an agent wrote or a page kept in
 * a database. The form stays the one that validates, saves and answers (its
 * processor); the vue only draws it, as lightly as it likes — a title edited
 * in place (inline-edit), the form whole (props-form).
 *
 * ```
 * {
 *   name: 'step_title_form',
 *   action: '/_forms/submit/step_title_form/entity/__id__',
 *   method: 'POST',
 *   token: { name: '_token', value: '…' },
 *   fields: [{ type: 'text', name: 'title', label: 'Title', required: true, maxLength: 255, value: 'Intro' }]
 * }
 * ```
 *
 * A field's name is its own: sent under the form's, `step_title_form[title]`,
 * as the drawn form sends it. Its label is the one the drawn form shows —
 * `label: true` read as `field.<name>.label` in the form's domain, as the
 * form theme reads it —, its name when the form says none. What is built is
 * checked against the norm before it leaves: a form the norm cannot say is
 * a fault here, not in the vue.
 */
class FormPropsBuilder
{
    private const string TOKEN_FIELD = '_token';

    private const string KIND_SUFFIX = '_input';

    private const string KIND_DEFAULT = 'text';

    public function __construct(
        private readonly FormSchemaLoader $schemas,
        private readonly FormRenderingService $rendering,
        private readonly ?TranslatorInterface $translator = null,
    ) {
    }

    /**
     * `id_placeholder`: the entity's id in the action replaced by it, so one
     * form serves every entity of its kind the vue shows — the steps of a
     * bar — and the vue puts the one it sends in its place.
     */
    public function build(FormInterface|FormView $form, array $options = []): array
    {
        $view = $form instanceof FormView ? $form : $form->createView();
        $vars = $view->vars;

        $props = [
            'name' => $vars['name'],
            // A form naming no address is sent to the page it is on, as the
            // drawn one would be.
            'action' => $this->buildAction($form, $vars['action'] ?? '', $options['id_placeholder'] ?? null) ?: '#',
            'method' => strtoupper($vars['method'] ?? 'POST'),
            'token' => null,
            'fields' => [],
        ];

        foreach ($view->children as $name => $child) {
            if (self::TOKEN_FIELD === $name) {
                $props['token'] = [
                    'name' => $name,
                    'value' => (string) $child->vars['value'],
                ];

                continue;
            }

            // A button is pressed, never filled in.
            if (! array_key_exists('value', $child->vars) || in_array('button', $child->vars['block_prefixes'] ?? [], true)) {
                continue;
            }

            $props['fields'][] = $this->buildField($child);
        }

        $this->rendering->validateAgainst($this->schemas->load('form'), $props, sprintf('The form "%s" handed to a vue', $vars['name']));

        return $props;
    }

    private function buildField(FormView $field): array
    {
        $vars = $field->vars;
        $attr = $vars['attr'] ?? [];
        $kind = $this->buildKind($field);
        $value = $vars['value'];

        $built = array_filter([
            'type' => $kind,
            'name' => $vars['name'],
            'label' => $this->buildLabel($field),
            'placeholder' => $this->buildPlaceholder($field),
            'required' => (bool) ($vars['required'] ?? false),
            'minLength' => isset($attr['minlength']) ? (int) $attr['minlength'] : null,
            'maxLength' => isset($attr['maxlength']) ? (int) $attr['maxlength'] : null,
            'pattern' => isset($attr['pattern']) ? (string) $attr['pattern'] : null,
            // What the norm holds as a value: a text. A list — a choice of
            // several — or a record goes as nothing, the field starting empty.
            'value' => is_scalar($value) ? (string) $value : null,
        ], static fn (mixed $value): bool => $value !== null);

        // A hidden field says no label.
        if ('hidden' === $kind) {
            unset($built['label']);
        }

        if ('textarea' === $kind && isset($attr['rows'])) {
            $built['rows'] = (int) $attr['rows'];
        }

        if ('select' === $kind) {
            $built['choices'] = $this->buildChoices($vars['choices'] ?? []);

            if (! empty($vars['multiple'])) {
                $built['multiple'] = true;
            }
        }

        return $built;
    }

    /**
     * The kind of field, as the norm names it: the nearest block prefix the
     * norm has a schema for — `textarea_input` is `textarea`, as a core
     * `email` is `email` —, text for any other.
     */
    private function buildKind(FormView $field): string
    {
        $kinds = $this->schemas->getFieldKinds();

        foreach (array_reverse($field->vars['block_prefixes'] ?? []) as $prefix) {
            $kind = str_ends_with($prefix, self::KIND_SUFFIX) ? substr($prefix, 0, -strlen(self::KIND_SUFFIX)) : $prefix;

            if (in_array($kind, $kinds, true)) {
                return $kind;
            }
        }

        return self::KIND_DEFAULT;
    }

    /**
     * @param array<ChoiceView|ChoiceGroupView> $choices
     *
     * @return array<array{label: string, value: string|null, options?: array}>
     */
    private function buildChoices(array $choices): array
    {
        $built = [];

        foreach ($choices as $choice) {
            if ($choice instanceof ChoiceGroupView) {
                $built[] = [
                    'label' => (string) $choice->label,
                    'value' => null,
                    'options' => $this->buildChoices($choice->choices),
                ];

                continue;
            }

            if ($choice instanceof ChoiceView) {
                $built[] = [
                    'label' => (string) $choice->label,
                    'value' => (string) $choice->value,
                ];
            }
        }

        return $built;
    }

    private function buildAction(FormInterface|FormView $form, string $action, ?string $placeholder): string
    {
        if (null === $placeholder || ! $form instanceof FormInterface) {
            return $action;
        }

        $data = $form->getData();
        $id = is_object($data) && method_exists($data, 'getId') ? $data->getId() : null;

        return null === $id ? $action : str_replace('/'.$id, '/'.$placeholder, $action);
    }

    private function buildLabel(FormView $field): string
    {
        $vars = $field->vars;
        $label = $vars['label'] ?? null;

        if (false === $label) {
            return $vars['name'];
        }

        $key = true === $label ? 'field.'.$vars['name'].'.label' : ($label ?? $vars['name']);

        return $this->translate($key, $vars['label_translation_parameters'] ?? [], $vars['translation_domain'] ?? null);
    }

    private function buildPlaceholder(FormView $field): ?string
    {
        $vars = $field->vars;
        $placeholder = $vars['attr']['placeholder'] ?? null;

        if (null === $placeholder || false === $placeholder) {
            return null;
        }

        $key = true === $placeholder ? 'field.'.$vars['name'].'.placeholder' : (string) $placeholder;

        return $this->translate($key, [], $vars['translation_domain'] ?? null);
    }

    private function translate(string $key, array $parameters, string|false|null $domain): string
    {
        return false === $domain || null === $this->translator
            ? $key
            : $this->translator->trans($key, $parameters, $domain);
    }
}
