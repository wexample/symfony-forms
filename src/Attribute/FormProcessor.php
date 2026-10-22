<?php

namespace Wexample\SymfonyForms\Attribute;

use Attribute;
use ReflectionClass;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class FormProcessor
{
    private const string ENTITY_FORM_SUFFIX = 'EntityForm';

    public function __construct(
        public string $processorClass,
        public string $formArgumentName,
        public ?string $formDataResolverClass = null,
        public array $formDataResolverOptions = []
    ) {
    }

    /**
     * The controller argument a processor's form arrives in, when the subclass
     * lets it be guessed: the form's short name, lowercased, with a
     * `…EntityForm` suffix brought back to `…Form`.
     */
    protected static function guessFormArgumentName(
        string $processorClass
    ): string {
        $formShortName = (new ReflectionClass($processorClass::getFormClass()))->getShortName();

        if (str_ends_with($formShortName, self::ENTITY_FORM_SUFFIX)) {
            $formShortName = substr($formShortName, 0, -strlen(self::ENTITY_FORM_SUFFIX)) . 'Form';
        }

        return lcfirst($formShortName);
    }
}
