<?php

namespace Wexample\SymfonyForms\Service;

use Wexample\PhpJsonSchema\Helper\JsonSchemaHelper;
use Wexample\SymfonyTemplate\Helper\TemplateHelper;

/**
 * A field drawn by a twig function rather than by a form (`text_input()`…):
 * its context checked against the schema of its kind before it is drawn,
 * so a page handing an option the field does not take is told at once.
 */
class FormRenderingService
{
    public const string FORM_TYPE_TEXT_INPUT = 'text_input';
    public const string FORM_TYPE_PASSWORD_INPUT = 'password_input';
    public const string FORM_TYPE_HIDDEN_INPUT = 'hidden_input';
    public const string FORM_TYPE_SUBMIT_INPUT = 'submit_input';
    public const string FORM_TYPE_TEXTAREA_INPUT = 'textarea_input';
    public const string FORM_TYPE_SELECT_INPUT = 'select_input';

    public function __construct(
        private readonly FormSchemaLoader $schemas,
    ) {
    }

    public function validate(array $context, string $type): void
    {
        $this->validateAgainst($this->schemas->load($type), TemplateHelper::stripTwigContextKeys($context), $type . ' context');
    }

    /**
     * Data checked against a schema of the norm; what does not match said.
     */
    public function validateAgainst(object $schema, array $data, string $label = 'data'): void
    {
        if ($faults = JsonSchemaHelper::validate($schema, $data)) {
            throw new \InvalidArgumentException(sprintf('%s does not match schema: %s', $label, $faults[0]));
        }
    }
}
