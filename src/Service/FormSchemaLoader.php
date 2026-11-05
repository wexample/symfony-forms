<?php

namespace Wexample\SymfonyForms\Service;

use Wexample\PhpJsonSchema\Class\JsonSchemaRegistry;
use Wexample\SymfonyDesignSystem\Service\FormSchemaService;
use Wexample\SymfonyHelpers\Attribute\WorkerSafe;

/**
 * The form schemas as a validator reads them: the design system's copy of
 * the json-schema norm (FormSchemaService), each resolved by php-json-schema
 * — every `$ref` in place, every `allOf` folded into one object refusing
 * what it does not name, the shape the validator used here checks best.
 *
 * Read once and kept: a schema is read on every drawn field.
 */
#[WorkerSafe('The schemas of the norm, read once from disk, the same for every request.')]
class FormSchemaLoader
{
    private ?JsonSchemaRegistry $registry = null;

    public function __construct(
        private readonly FormSchemaService $schemas,
    ) {
    }

    /**
     * `text_input`, `form`, `form_submit_response`…
     */
    public function load(string $name): object
    {
        return $this->getRegistry()->resolve($name);
    }

    /**
     * The kinds of field the schemas know, as a field says its kind.
     *
     * @return string[]
     */
    public function getFieldKinds(): array
    {
        return $this->schemas->getFieldKinds();
    }

    /** The norm's copy, by file name: what a consumer resolves its own schemas against. */
    public function getRegistry(): JsonSchemaRegistry
    {
        return $this->registry ??= (new JsonSchemaRegistry())->addDirectory($this->schemas->getDirectory());
    }
}
