<?php

namespace Wexample\SymfonyForms\Service;

use Symfony\Component\Form\ChoiceList\View\ChoiceGroupView;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A form handed to a vue rather than drawn (`form_definition()`): the form as the
 * json-schema norm describes one (`form.schema.json` — its fields each of the kind
 * the norm names, where and how it is sent), so the vue draws it from that
 * description alone, as it would draw one an agent wrote or a page kept in
 * a database. The form stays the one that validates, saves and answers (its
 * processor); the vue only draws it, as lightly as it likes — a title edited
 * in place (inline-edit), the form whole (dynamic-form).
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
class FormDefinitionBuilder
{
    private const string TOKEN_FIELD = '_token';

    private const string KIND_SUFFIX = '_input';

    private const string KIND_DEFAULT = 'text';

    // The design system's columns, as a form drawn by twig carries them
    // (`attr`, `row_attr`): `form--columns` is three, `form--columns-2` two.
    private const string CLASS_COLUMNS = 'form--columns';

    private const string CLASS_COLUMNS_TWO = 'form--columns-2';

    private const string CLASS_SPAN = 'form--span';

    private const int COLUMNS_DEFAULT = 3;

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
            'columns' => $this->buildColumns($vars['attr']['class'] ?? ''),
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

        // The whole line among columns, as its row says (`row_attr`).
        if (in_array(self::CLASS_SPAN, preg_split('/\s+/', (string) ($vars['row_attr']['class'] ?? '')), true)) {
            $built['span'] = true;
        }

        // A hidden field says no label.
        if ('hidden' === $kind) {
            unset($built['label']);
        }

        if ('textarea' === $kind && isset($attr['rows'])) {
            $built['rows'] = (int) $attr['rows'];
        }

        // Pairs, in the order written: the field's value is a list, not a
        // text (`key_value_input.schema.json`).
        if ('key_value' === $kind) {
            $built['pairs'] = array_values(array_map(
                static fn (array $pair): array => ['key' => (string) $pair['key'], 'value' => (string) $pair['value']],
                array_filter(is_array($value) ? $value : [], static fn (mixed $pair): bool => is_array($pair) && '' !== (string) ($pair['key'] ?? ''))
            ));
        }

        // The words, a list; what is offered and how a tag is drawn, as the
        // twig field has them (`tags_input.schema.json`).
        if ('tags' === $kind) {
            unset($built['value']);
            $built['tags'] = $vars['tags'] ?? [];
            $built['separator'] = (string) ($vars['separator'] ?? ',');
            $built['tone'] = (string) ($vars['tone'] ?? 'neutral');

            if (! empty($vars['suggestions'])) {
                $built['suggestions'] = $vars['suggestions'];
            }

            if (! empty($vars['suggestions_url'])) {
                $built['suggestionsUrl'] = (string) $vars['suggestions_url'];
            }

            if (! empty($vars['glyph'])) {
                $built['glyph'] = (string) $vars['glyph'];
            }
        }

        // A choice searched: the options it holds, its source, the label of
        // its value (`search_select_input.schema.json`).
        if ('search_select' === $kind) {
            if (! empty($vars['options'])) {
                $built['options'] = $vars['options'];
            }

            if (! empty($vars['options_url'])) {
                $built['optionsUrl'] = (string) $vars['options_url'];
            }

            if (null !== ($vars['value_label'] ?? null)) {
                $built['valueLabel'] = (string) $vars['value_label'];
            }
        }

        // Code: its language, its own or another field's (`code_input.schema.json`),
        // that field named as the definition names fields — by its name, not
        // the id the drawn form gives it (`language_source`).
        if ('code' === $kind) {
            if (! empty($vars['language'])) {
                $built['language'] = (string) $vars['language'];
            }

            $source = (string) ($vars['language_source'] ?? '');
            $prefix = ($field->parent?->vars['id'] ?? '').'_';

            if ('' !== $source && str_starts_with($source, $prefix)) {
                $built['languageField'] = substr($source, strlen($prefix));
            }

            if (! empty($vars['language_map'])) {
                $built['languageMap'] = array_map('strval', $vars['language_map']);
            }

            if (isset($attr['rows'])) {
                $built['rows'] = (int) $attr['rows'];
            }
        }

        // A colour: the ones it offers (`color_input.schema.json`).
        if ('color' === $kind && ! empty($vars['swatches'])) {
            $built['swatches'] = $vars['swatches'];
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
    // How many fields side by side, as the form's classes say; one else.
    private function buildColumns(string $classes): int
    {
        $classes = preg_split('/\s+/', $classes);

        if (in_array(self::CLASS_COLUMNS_TWO, $classes, true)) {
            return 2;
        }

        return in_array(self::CLASS_COLUMNS, $classes, true) ? self::COLUMNS_DEFAULT : 1;
    }

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
