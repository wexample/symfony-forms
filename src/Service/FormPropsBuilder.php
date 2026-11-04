<?php

namespace Wexample\SymfonyForms\Service;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A form handed to a vue rather than drawn (`form_props()`): what the vue
 * needs to send it as the form drawn would be sent — where, under which
 * name, with its token —, and each field's name, value and limits. The form
 * stays the one that validates, saves and answers (its processor); the vue
 * only draws it, as lightly as it likes — a title edited in place.
 *
 * ```
 * {
 *   name: 'step_title_form',
 *   action: '/_forms/submit/step_title_form/entity/__id__',
 *   method: 'POST',
 *   token: { name: 'step_title_form[_token]', value: '…' },
 *   fields: { title: { name: 'step_title_form[title]', value: 'Intro', required: true, maxlength: 255, label: 'Title', type: 'text' } }
 * }
 * ```
 *
 * `label` is the one the drawn form shows — `label: true` read as
 * `field.<name>.label` in the form's domain, as the form theme reads it —,
 * null when the form says none; `type` the kind of field a vue draws for it
 * (FIELD_TYPES), `text` for any other.
 */
class FormPropsBuilder
{
    private const string TOKEN_FIELD = '_token';

    /** The block prefix of a field's type, and the kind of field it is drawn as. */
    private const array FIELD_TYPES = [
        'textarea_input' => 'textarea',
        'email_input' => 'email',
        'password_input' => 'password',
        'tel_input' => 'tel',
        'url_input' => 'url',
        'number_input' => 'number',
        'text_input' => 'text',
    ];

    public function __construct(
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
            'name' => $vars['full_name'],
            'action' => $this->buildAction($form, $vars['action'] ?? '', $options['id_placeholder'] ?? null),
            'method' => strtoupper($vars['method'] ?? 'POST'),
            'token' => null,
            'fields' => [],
        ];

        foreach ($view->children as $name => $child) {
            if (self::TOKEN_FIELD === $name) {
                $props['token'] = [
                    'name' => $child->vars['full_name'],
                    'value' => $child->vars['value'],
                ];

                continue;
            }

            // A button is pressed, never filled in.
            if (! array_key_exists('value', $child->vars) || in_array('button', $child->vars['block_prefixes'] ?? [], true)) {
                continue;
            }

            $props['fields'][$name] = [
                'name' => $child->vars['full_name'],
                'value' => $child->vars['value'],
                'required' => (bool) ($child->vars['required'] ?? false),
                'maxlength' => isset($child->vars['attr']['maxlength']) ? (int) $child->vars['attr']['maxlength'] : null,
                'label' => $this->buildLabel($child),
                'type' => $this->buildType($child),
            ];
        }

        return $props;
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

    private function buildLabel(FormView $field): ?string
    {
        $vars = $field->vars;
        $label = $vars['label'] ?? null;

        if (false === $label) {
            return null;
        }

        $key = true === $label ? 'field.'.$vars['name'].'.label' : ($label ?? $vars['name']);
        $domain = $vars['translation_domain'] ?? null;

        return false === $domain || null === $this->translator
            ? $key
            : $this->translator->trans($key, $vars['label_translation_parameters'] ?? [], $domain);
    }

    private function buildType(FormView $field): string
    {
        foreach (array_reverse($field->vars['block_prefixes'] ?? []) as $prefix) {
            if (isset(self::FIELD_TYPES[$prefix])) {
                return self::FIELD_TYPES[$prefix];
            }
        }

        return 'text';
    }
}
