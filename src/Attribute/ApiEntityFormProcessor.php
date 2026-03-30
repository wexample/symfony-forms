<?php

namespace Wexample\SymfonyForms\Attribute;

use Attribute;
use ReflectionClass;
use Wexample\SymfonyForms\Service\FormProcessor\DataResolver\EntityEditFormDataResolver;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiEntityFormProcessor extends FormProcessor
{
    private const string ENTITY_FORM_SUFFIX = 'EntityForm';

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

    private static function guessFormArgumentName(
        string $processorClass
    ): string {
        $formClass = $processorClass::getFormClass();
        $formShortName = (new ReflectionClass($formClass))->getShortName();

        if (str_ends_with($formShortName, self::ENTITY_FORM_SUFFIX)) {
            $formShortName = substr($formShortName, 0, -strlen(self::ENTITY_FORM_SUFFIX)) . 'Form';
        }

        return lcfirst($formShortName);
    }
}
