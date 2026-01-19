<?php

namespace Wexample\SymfonyForms\Twig;

use Twig\Environment;
use Twig\TwigFunction;

class FormExtension extends \Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'form_input',
                function (Environment $twig, array $context = []) {
                    $context = $this->normalizeInputContext($context);

                    return $this->renderTemplate(
                        $twig,
                        '@WexampleSymfonyFormsBundle/components/form_input.html.twig',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }

    private function normalizeInputContext(array $context): array
    {
        if (isset($context['schema']) && is_array($context['schema'])) {
            $schema = $context['schema'];

            if (!isset($context['label']) && isset($schema['title'])) {
                $context['label'] = $schema['title'];
            }

            if (!isset($context['placeholder']) && isset($schema['description'])) {
                $context['placeholder'] = $schema['description'];
            }

            if (!isset($context['type']) && isset($schema['type'])) {
                $context['type'] = $this->mapSchemaType($schema);
            }

            if (!isset($context['required'])) {
                $context['required'] = $this->inferRequired($schema);
            }

            if (!isset($context['value']) && array_key_exists('default', $schema)) {
                $context['value'] = $schema['default'];
            }
        }

        if (!isset($context['id']) && isset($context['name'])) {
            $context['id'] = $context['name'];
        }

        return $context;
    }

    private function inferRequired(array $schema): bool
    {
        if (isset($schema['required']) && $schema['required'] === true) {
            return true;
        }

        if (isset($schema['minLength']) && is_int($schema['minLength'])) {
            return $schema['minLength'] > 0;
        }

        return false;
    }

    private function mapSchemaType(array $schema): string
    {
        $type = $schema['type'] ?? 'string';
        $format = $schema['format'] ?? null;

        if ($type === 'string' && is_string($format)) {
            $formatMap = [
                'email' => 'email',
                'uri' => 'url',
                'date' => 'date',
                'date-time' => 'datetime-local',
            ];

            if (isset($formatMap[$format])) {
                return $formatMap[$format];
            }
        }

        if ($type === 'integer' || $type === 'number') {
            return 'number';
        }

        if ($type === 'boolean') {
            return 'checkbox';
        }

        return 'text';
    }
}
