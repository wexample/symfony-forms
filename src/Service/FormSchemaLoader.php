<?php

namespace Wexample\SymfonyForms\Service;

use Wexample\SymfonyDesignSystem\Service\FormSchemaService;
use Wexample\SymfonyHelpers\Attribute\WorkerSafe;

/**
 * The form schemas as a validator reads them: the design system's copy of
 * the json-schema norm (FormSchemaService), each file's relative `$ref`s put
 * in place, each `allOf` folded into one object refusing what it does not
 * name — the shape the validator used here checks best.
 *
 * Loaded once per name and kept: a schema is read on every drawn field.
 */
#[WorkerSafe('The schemas of the norm, read once from disk, the same for every request.')]
class FormSchemaLoader
{
    /** @var array<string, object> */
    private array $loaded = [];

    /** @var array<string, object> */
    private array $files = [];

    public function __construct(
        private readonly FormSchemaService $schemas,
    ) {
    }

    /**
     * `text_input`, `form`, `form_submit_response`…
     */
    public function load(string $name): object
    {
        if (! isset($this->loaded[$name])) {
            $schema = $this->fold($this->inline($this->read($name), [$name]));
            // The norm names its files by relative ids, for a copy to resolve
            // wherever it lands; inlined whole, the schema needs none, and the
            // validator wants an absolute one or nothing.
            unset($schema->{'$id'});
            $this->loaded[$name] = $schema;
        }

        return $this->loaded[$name];
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

    private function read(string $name): object
    {
        if (isset($this->files[$name])) {
            return clone $this->files[$name];
        }

        $path = $this->schemas->getPath($name);

        if (! is_file($path)) {
            throw new \InvalidArgumentException(sprintf('No form schema "%s" (%s).', $name, $path));
        }

        $schema = json_decode(file_get_contents($path), false, 512, JSON_THROW_ON_ERROR);
        $this->files[$name] = $schema;

        return clone $schema;
    }

    /**
     * A `$ref` to a file beside this one replaced by that file, itself
     * inlined; a file referring to itself through others is left as a
     * reference, which is never the case in the norm.
     *
     * @param string[] $stack the names being inlined, outermost first
     */
    private function inline(mixed $node, array $stack): mixed
    {
        if (is_array($node)) {
            return array_map(fn (mixed $value): mixed => $this->inline($value, $stack), $node);
        }

        if (! is_object($node)) {
            return $node;
        }

        if (isset($node->{'$ref'}) && is_string($node->{'$ref'}) && str_ends_with($node->{'$ref'}, '.json')) {
            $name = pathinfo(explode('#', $node->{'$ref'})[0], PATHINFO_FILENAME);

            if (! in_array($name, $stack, true)) {
                $target = $this->inline($this->read($name), [...$stack, $name]);
                unset($target->{'$id'}, $target->{'$schema'});

                return $target;
            }
        }

        foreach (get_object_vars($node) as $key => $value) {
            $node->{$key} = $this->inline($value, $stack);
        }

        return $node;
    }

    /**
     * An object's `allOf` folded into it — properties and required merged —,
     * and what it leaves unevaluated refused as what is additional: the one
     * reading of the norm's `unevaluatedProperties: false` every validator
     * agrees on. Folded as deep as the schema goes.
     */
    private function fold(mixed $node): mixed
    {
        if (is_array($node)) {
            return array_map(fn (mixed $value): mixed => $this->fold($value), $node);
        }

        if (! is_object($node)) {
            return $node;
        }

        foreach (get_object_vars($node) as $key => $value) {
            $node->{$key} = $this->fold($value);
        }

        if (isset($node->allOf) && is_array($node->allOf)) {
            $properties = (array) ($node->properties ?? []);
            $required = is_array($node->required ?? null) ? $node->required : [];

            foreach ($node->allOf as $entry) {
                if (! is_object($entry)) {
                    continue;
                }

                $properties = array_merge($properties, (array) ($entry->properties ?? []));
                $required = array_merge($required, is_array($entry->required ?? null) ? $entry->required : []);
            }

            $node->properties = (object) $properties;
            $node->required = array_values(array_unique($required));
            unset($node->allOf);
        }

        if (isset($node->unevaluatedProperties)) {
            $node->additionalProperties = $node->unevaluatedProperties;
            unset($node->unevaluatedProperties);
        }

        return $node;
    }
}
