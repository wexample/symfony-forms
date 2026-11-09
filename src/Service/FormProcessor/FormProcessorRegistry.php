<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Wexample\SymfonyForms\Exception\AmbiguousFormProcessorException;

/**
 * Which processor handles which form, read from maps built once, when the
 * container is compiled (FormProcessorRegistryPass).
 *
 * The lookup used to walk every processor and ask each its form class until
 * one matched; one processor unable to answer — a form not written yet, a
 * renamed class — then took down every submission of the application. Asked
 * at compile time, the same question fails the build instead.
 *
 * A form may have several processors, each picked by the page that posts it
 * through `#[FormProcessor]`. Looked up by its name or its class — its submit
 * address, `form_definition_for()` — such a form has no single answer, and
 * the lookup says so for that form alone.
 */
class FormProcessorRegistry
{
    /**
     * @param array<class-string, list<string>> $processorsByFormClass form class => processor service ids
     * @param array<string, list<string>>       $processorsByFormName  form name, as in the submit address => processor service ids
     */
    public function __construct(
        private readonly ServiceLocator $processors,
        private readonly array $processorsByFormClass = [],
        private readonly array $processorsByFormName = [],
    ) {
    }

    public function findByFormClass(string $formClass): ?AbstractFormProcessor
    {
        return $this->get($formClass, $this->processorsByFormClass[$formClass] ?? []);
    }

    public function findByFormName(string $formName): ?AbstractFormProcessor
    {
        return $this->get($formName, $this->processorsByFormName[$formName] ?? []);
    }

    /**
     * @param list<string> $serviceIds
     */
    private function get(string $form, array $serviceIds): ?AbstractFormProcessor
    {
        if (count($serviceIds) > 1) {
            throw new AmbiguousFormProcessorException($form, $serviceIds);
        }

        return [] === $serviceIds ? null : $this->processors->get($serviceIds[0]);
    }
}
