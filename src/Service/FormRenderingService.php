<?php

namespace Wexample\SymfonyForms\Service;

use Syrtis\JsonSchema\Helper\JsonSchemaValidationHelper;
use Syrtis\SemanticSchemaWeb\Helper\SchemaLoaderHelper;
use Wexample\SymfonyTemplate\Helper\TemplateHelper;

class FormRenderingService
{
    public const string FORM_TYPE_TEXT_INPUT = 'text_input';
    public const string FORM_TYPE_HIDDEN_INPUT = 'hidden_input';
    public const string FORM_TYPE_SUBMIT_INPUT = 'submit_input';

    public function validate(array $context, string $type): void
    {
        $schema = SchemaLoaderHelper::loadSchema($type);
        $data = TemplateHelper::stripTwigContextKeys($context);
        JsonSchemaValidationHelper::validateOrThrow(
            $schema,
            $data,
            $type.' context'
        );
    }
}
