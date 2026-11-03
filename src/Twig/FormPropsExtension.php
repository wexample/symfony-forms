<?php

namespace Wexample\SymfonyForms\Twig;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyForms\Service\FormPropsBuilder;

/**
 * `form_props(form, { id_placeholder })`: a form handed to a vue rather than
 * drawn (FormPropsBuilder) — the props of the design system's `inline-edit`,
 * of any vue sending a form of the application.
 */
class FormPropsExtension extends AbstractExtension
{
    public function __construct(
        private readonly FormPropsBuilder $builder,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'form_props',
                fn (FormInterface|FormView $form, array $options = []): array => $this->builder->build($form, $options)
            ),
        ];
    }
}
