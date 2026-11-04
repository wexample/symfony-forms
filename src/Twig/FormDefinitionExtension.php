<?php

namespace Wexample\SymfonyForms\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyForms\Service\FormDefinitionBuilder;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractEntityFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;

/**
 * A form handed to a vue rather than drawn (FormDefinitionBuilder): its
 * definition, what the design system's `inline-edit` and `dynamic-form` draw
 * from, what any vue sending a form of the application reads:
 *
 * - `form_definition(form, { id_placeholder })`, a form the controller made;
 * - `form_definition_for(form_class, data, { id_placeholder })`, a form made here by
 *   its processor, for what a layout shows on every page it draws — the
 *   title of the record a header names —, no controller making it for each.
 *   No data and a placeholder: the form of an entity of its kind, any of
 *   them, its id put in its address by the vue — the slot a board's zone
 *   edits, whichever is picked.
 */
class FormDefinitionExtension extends AbstractExtension
{
    public function __construct(
        private readonly FormDefinitionBuilder $builder,
        private readonly ServiceLocator $processors,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'form_definition',
                fn (FormInterface|FormView $form, array $options = []): array => $this->builder->build($form, $options)
            ),
            new TwigFunction(
                'form_definition_for',
                fn (string $formClass, mixed $data = null, array $options = []): array => $this->builder->build(
                    $this->createFormFor($formClass, $data, $options),
                    $options
                )
            ),
        ];
    }

    private function createFormFor(string $formClass, mixed $data, array $options): FormInterface
    {
        $processor = $this->processorFor($formClass);
        $placeholder = $options['id_placeholder'] ?? null;

        if (null === $data && null !== $placeholder && $processor instanceof AbstractEntityFormProcessor) {
            return $processor->createForm(null, ['action' => $processor->createFormActionForId($placeholder)]);
        }

        return $processor->createForm($data);
    }

    private function processorFor(string $formClass): AbstractFormProcessor
    {
        foreach (array_keys($this->processors->getProvidedServices()) as $processorClass) {
            /** @var class-string<AbstractFormProcessor> $processorClass */
            if ($processorClass::getFormClass() === $formClass) {
                return $this->processors->get($processorClass);
            }
        }

        throw new \InvalidArgumentException('No form processor for '.$formClass.'.');
    }
}
