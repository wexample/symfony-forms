<?php

namespace Wexample\SymfonyForms\Service;

use Opis\JsonSchema\Validator;
use Wexample\SymfonyHelpers\Helper\JsonHelper;

class FormRenderingService
{
    public function prepare(array $context, string $type): array
    {
        $context = $this->normalize($context);
        $this->validate($context, $type);

        return $context;
    }

    private function normalize(array $context): array
    {
        if (!isset($context['id']) && isset($context['name'])) {
            $context['id'] = $context['name'];
        }

        return $context;
    }

    private function validate(array $context, string $type): void
    {
        $schemaPath = $this->getInputSchemaPath($type);
        $schema = JsonHelper::readOrNull($schemaPath);
        if ($schema === null) {
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
                '%s context does not match schema: %s',
                $type,
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

    private function getInputSchemaPath(string $type): string
    {
        return __DIR__.'/../../assets/schemas/' . $type . '.json';
    }
}
