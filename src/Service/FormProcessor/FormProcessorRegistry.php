<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * Which processor handles which form, read from maps built once, when the
 * container is compiled (FormProcessorRegistryPass).
 *
 * The lookup used to walk every processor and ask each its form class until
 * one matched; one processor unable to answer — a form not written yet, a
 * renamed class — then took down every submission of the application. Asked
 * at compile time, the same question fails the build instead.
 */
class FormProcessorRegistry
{
    /**
     * @param array<class-string, string> $processorsByFormClass form class => processor service id
     * @param array<string, string>       $processorsByFormName  form name, as in the submit address => processor service id
     */
    public function __construct(
        private readonly ServiceLocator $processors,
        private readonly array $processorsByFormClass = [],
        private readonly array $processorsByFormName = [],
    ) {
    }

    public function findByFormClass(string $formClass): ?AbstractFormProcessor
    {
        return $this->get($this->processorsByFormClass[$formClass] ?? null);
    }

    public function findByFormName(string $formName): ?AbstractFormProcessor
    {
        return $this->get($this->processorsByFormName[$formName] ?? null);
    }

    private function get(?string $serviceId): ?AbstractFormProcessor
    {
        return null === $serviceId ? null : $this->processors->get($serviceId);
    }
}
