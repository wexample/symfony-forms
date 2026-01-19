<?php

namespace Wexample\SymfonyForms\Service;

use Opis\JsonSchema\Validator;

class FormRenderingService
{
    public function prepare(array $context): array
    {
        $context = $this->normalize($context);
        $this->validate($context);

        return $context;
    }

    private function normalize(array $context): array
    {
        if (!isset($context['id']) && isset($context['name'])) {
            $context['id'] = $context['name'];
        }

        return $context;
    }

    private function validate(array $context): void
    {
        $schemaPath = $this->resolveSchemaPath($context['schema_path'] ?? null);
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
            if ($error) {
                $path = $this->formatErrorPath($error->data()->fullPath());
                $message = sprintf(
                    '%s at %s',
                    $error->message(),
                    $path
                );
            } else {
                $message = 'unknown error';
            }

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
            'schema_path',
        ];

        foreach ($excludeKeys as $key) {
            unset($context[$key]);
        }

        return $context;
    }

    /**
     * @param array<int|string> $path
     */
    private function formatErrorPath(array $path): string
    {
        if ($path === []) {
            return '/';
        }

        $encoded = array_map(
            static function ($segment): string {
                $segment = (string) $segment;

                return str_replace(['~', '/'], ['~0', '~1'], $segment);
            },
            $path
        );

        return '/'.implode('/', $encoded);
    }

    private function getInputSchemaPath(): string
    {
        return __DIR__.'/../../assets/schemas/form_input.json';
    }

    private function resolveSchemaPath(?string $schemaPath): string
    {
        if ($schemaPath === null || $schemaPath === '') {
            return $this->getInputSchemaPath();
        }

        if (str_starts_with($schemaPath, '/')) {
            return $schemaPath;
        }

        if (preg_match('/^[A-Za-z]:\\\\/', $schemaPath)) {
            return $schemaPath;
        }

        return __DIR__.'/../../'.$schemaPath;
    }
}
