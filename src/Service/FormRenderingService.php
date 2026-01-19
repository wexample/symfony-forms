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
        $message = $error ? $error->message() : 'unknown error';

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
}
