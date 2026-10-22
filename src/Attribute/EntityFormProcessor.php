<?php

namespace Wexample\SymfonyForms\Attribute;

use Attribute;
use Wexample\SymfonyForms\Service\FormProcessor\DataResolver\EntityFormDataResolver;

/**
 * Wires a controller method to the processor of an entity edit form.
 *
 * The counterpart of `ApiEntityFormProcessor` for a form that edits the
 * Doctrine entity itself rather than posting to an API. Only the entity varies,
 * so it is the one thing left to declare.
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class EntityFormProcessor extends FormProcessor
{
    public function __construct(
        string $processorClass,
        string $entityType,
        ?string $formArgumentName = null,
        ?string $formDataResolverClass = EntityFormDataResolver::class,
        array $formDataResolverOptions = []
    ) {
        parent::__construct(
            processorClass: $processorClass,
            formArgumentName: $formArgumentName ?? self::guessFormArgumentName($processorClass),
            formDataResolverClass: $formDataResolverClass,
            formDataResolverOptions: [
                ...$formDataResolverOptions,
                EntityFormDataResolver::OPTION_ENTITY_TYPE => $entityType,
            ]
        );
    }
}
