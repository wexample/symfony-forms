<?php

namespace Wexample\SymfonyForms\Service;

use Opis\JsonSchema\Validator;
use Wexample\SymfonyHelpers\Helper\JsonHelper;
use Wexample\SymfonyTemplate\Helper\TemplateHelper;
use Wexample\SymfonyJsonSchema\Helper\JsonSchemaHelper;

class FormRenderingService
{
    public const string FORM_TYPE_TEXT_INPUT = 'text_input';
    public const string FORM_TYPE_HIDDEN_INPUT = 'hidden_input';

    public function validate(array $context, string $type): void
    {
        $schema = $this->buildSchema($type);
        $dataObject = JsonHelper::toObject(
            TemplateHelper::stripTwigContextKeys($context)
        );
        $validator = $this->createValidator();
        $result = $validator->validate($dataObject, $schema);

        if ($result->isValid()) {
            return;
        }

        $error = $result->error();
        $message = $error ? $this->formatErrorMessage($error) : 'unknown error';

        throw new \InvalidArgumentException(sprintf(
            '%s context does not match schema: %s',
            $type,
            $message
        ));
    }

    private function getInputSchemaPath(string $type): string
    {
        return __DIR__.'/../../assets/schemas/' . $type . '.json';
    }

    private function buildSchema(string $type): object
    {
        $schema = JsonHelper::read($this->getInputSchemaPath($type));
        $base = JsonHelper::read(__DIR__.'/../../assets/schemas/field_base.json');

        if (! is_object($schema) || ! is_object($base)) {
            return $schema;
        }

        return JsonSchemaHelper::mergeSchemaObjects($schema, $base);
    }

    private function createValidator(): Validator
    {
        $validator = new Validator();
        $this->registerBaseSchema($validator);

        return $validator;
    }

    private function registerBaseSchema(Validator $validator): void
    {
        $schemaPath = __DIR__.'/../../assets/schemas/field_base.json';
        $schema = JsonHelper::read($schemaPath);
        $schemaId = is_object($schema) ? ($schema->{'$id'} ?? null) : null;

        if (! $schemaId) {
            return;
        }

        $resolver = $validator->loader()->resolver();
        if (! $resolver) {
            return;
        }

        $resolver->registerRaw($schema, $schemaId);
    }

    private function formatErrorMessage(object $error): string
    {
        $message = $error->message();
        $args = $error->args();

        if (! is_array($args) || $args === []) {
            return $message;
        }

        foreach ($args as $key => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map('strval', $value));
            } elseif (! is_scalar($value)) {
                $value = (string) $value;
            }

            $message = str_replace('{'.$key.'}', (string) $value, $message);
        }

        return $message;
    }
}
