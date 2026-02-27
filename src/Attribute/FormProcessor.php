<?php

namespace Wexample\SymfonyForms\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class FormProcessor
{
    public function __construct(
        public string $processorClass,
        public string $formArgumentName,
        public ?string $formDataResolverClass = null,
        public array $formDataResolverOptions = []
    ) {
    }
}
