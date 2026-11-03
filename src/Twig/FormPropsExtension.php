<?php

namespace Wexample\SymfonyForms\Twig;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Service\FormPropsBuilder;

/**
 * A form handed to a vue rather than drawn (FormPropsBuilder) — the props of
 * the design system's `inline-edit`, of any vue sending a form of the
 * application:
 *
 * - `form_props(form, { id_placeholder })`, a form the controller made;
 * - `form_props_for(form_class, data, { id_placeholder })`, a form made here by
 *   its processor, for what a layout shows on every page it draws — the
 *   title of the record a header names —, no controller making it for each.
 */
class FormPropsExtension extends AbstractExtension
{
    public function __construct(
        private readonly FormPropsBuilder $builder,
        private readonly ServiceLocator $processors,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'form_props',
                fn (FormInterface|FormView $form, array $options = []): array => $this->builder->build($form, $options)
            ),
            new TwigFunction(
                'form_props_for',
                fn (string $formClass, mixed $data = null, array $options = []): array => $this->builder->build(
                    $this->processorFor($formClass)->createForm($data),
                    $options
                )
            ),
        ];
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
