<?php

namespace Wexample\SymfonyForms\Twig;

use Twig\Environment;
use Twig\TwigFunction;
use Opis\JsonSchema\Validator;

class FormExtension extends \Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'form_input',
                function (Environment $twig, array $context = []) {
                    $context = $this->normalizeInputContext($context);
                    $this->validateInputContext($context);

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
        if (!isset($context['id']) && isset($context['name'])) {
            $context['id'] = $context['name'];
        }

        return $context;
    }

    private function validateInputContext(array $context): void
    {
        if (! class_exists(Validator::class)) {
            return;
        }

        $schemaPath = $this->getInputSchemaPath();
        $schemaJson = file_get_contents($schemaPath);
        if ($schemaJson === false) {
            throw new \InvalidArgumentException(sprintf(
                'Unable to read JSON schema file: %s',
                $schemaPath
            ));
        }

        $schema = json_decode($schemaJson);
        if ($schema === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid JSON schema file: %s',
                $schemaPath
            ));
        }

        $data = $this->stripTwigContext($context);
        $dataObject = json_decode(json_encode($data, JSON_THROW_ON_ERROR));

        $validator = new Validator();
        $result = $validator->validate($dataObject, $schema);

        if (! $result->isValid()) {
            $error = $result->error();
            $message = $error
                ? sprintf(
                    '%s at %s',
                    $error->keyword(),
                    $error->dataPath() ?: '/'
                )
                : 'unknown error';

            throw new \InvalidArgumentException(sprintf(
                'form_input context does not match schema: %s',
                $message
            ));
        }
    }

    private function stripTwigContext(array $context): array
    {
        $excludeKeys = [
            'id',
            'value_attr',
            'input_attr',
            'error_condition',
            'error_text',
        ];

        foreach ($excludeKeys as $key) {
            unset($context[$key]);
        }

        return $context;
    }

    private function getInputSchemaPath(): string
    {
        return __DIR__.'/../../assets/schemas/form_input.json';
    }
}
