<?php

namespace Wexample\SymfonyForms\Service;

use Opis\JsonSchema\Validator;
use Wexample\SymfonyHelpers\Helper\JsonHelper;
use Wexample\SymfonyTemplate\Helper\TemplateHelper;

class FormRenderingService
{

    public function validate(array $context, string $type): void
    {
        $schemaPath = $this->getInputSchemaPath($type);
        $schema = JsonHelper::readOrNull($schemaPath);
        if ($schema === null) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid JSON schema file: %s',
                $schemaPath
            ));
        }

        $data = TemplateHelper::stripTwigContextKeys($context);
        $dataObject = JsonHelper::toObject($data);

        $validator = new Validator();
        $result = $validator->validate($dataObject, $schema);

        if (! $result->isValid()) {
            $error = $result->error();
            $message = $error ? $error->message() : 'unknown error';

            throw new \InvalidArgumentException(sprintf(
                '%s context does not match schema: %s',
                $type,
                $message
            ));
        }
    }

    private function getInputSchemaPath(string $type): string
    {
        return __DIR__.'/../../assets/schemas/' . $type . '.json';
    }
}
