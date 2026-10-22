<?php

namespace Wexample\SymfonyForms\Attribute;

use Attribute;
use Wexample\SymfonyForms\Service\FormProcessor\DataResolver\EntityEditFormDataResolver;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiEntityFormProcessor extends FormProcessor
{
    public function __construct(
        string $processorClass,
        ?string $formArgumentName = null,
        ?string $formDataResolverClass = EntityEditFormDataResolver::class,
        array $formDataResolverOptions = []
    ) {
        parent::__construct(
            processorClass: $processorClass,
            formArgumentName: $formArgumentName ?? self::guessFormArgumentName($processorClass),
            formDataResolverClass: $formDataResolverClass,
            formDataResolverOptions: [
                ...$formDataResolverOptions,
                'entityType' => $processorClass::getEntityClass(),
            ]
        );
    }
}
