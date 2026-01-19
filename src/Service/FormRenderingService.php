<?php

namespace Wexample\SymfonyForms\Service;

use Opis\JsonSchema\Validator;
use Wexample\SymfonyHelpers\Helper\JsonHelper;
use Wexample\SymfonyTemplate\Helper\TemplateHelper;

class FormRenderingService
{
    public const string FORM_TYPE_INPUT = 'form_input';

    public function validate(array $context, string $type): void
    {
        $schema = JsonHelper::read($this->getInputSchemaPath($type));
        $dataObject = JsonHelper::toObject(
            TemplateHelper::stripTwigContextKeys($context)
        );
        $result = (new Validator())->validate($dataObject, $schema);

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
