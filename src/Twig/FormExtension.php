<?php

namespace Wexample\SymfonyForms\Twig;

use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyForms\Service\FormRenderingService;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;

class FormExtension extends \Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension
{
    public function __construct(
        ComponentsExtension $componentsExtension,
        private readonly FormRenderingService $contextService,
    ) {
        parent::__construct($componentsExtension);
    }

    /**
     * Each function, the `type` it sets in the context and the component that
     * draws it. One row a field: they differ by nothing else.
     */
    private const array FIELDS = [
        FormRenderingService::FORM_TYPE_TEXT_INPUT => ['text', '@WexampleSymfonyDesignSystemBundle/components/form/text-input'],
        FormRenderingService::FORM_TYPE_PASSWORD_INPUT => ['password', '@WexampleSymfonyDesignSystemBundle/components/form/password-input'],
        FormRenderingService::FORM_TYPE_HIDDEN_INPUT => ['hidden', '@WexampleSymfonyDesignSystemBundle/components/form/hidden-input'],
        FormRenderingService::FORM_TYPE_SUBMIT_INPUT => ['submit', '@WexampleSymfonyDesignSystemBundle/components/form/submit-input'],
        FormRenderingService::FORM_TYPE_TEXTAREA_INPUT => ['textarea', '@WexampleSymfonyDesignSystemBundle/components/form/textarea-input'],
        FormRenderingService::FORM_TYPE_SELECT_INPUT => ['select', '@WexampleSymfonyDesignSystemBundle/components/form/select-input'],
    ];

    public function getFunctions(): array
    {
        $functions = [];

        foreach (self::FIELDS as $name => [$type, $component]) {
            $functions[] = new TwigFunction(
                $name,
                function (
                    Environment $twig,
                    $twigContext,
                    array $context = []
                ) use ($name, $type, $component) {
                    $context['type'] = $type;
                    $this->contextService->validate($context, $name);

                    return $this->renderComponent(
                        $twig,
                        $twigContext,
                        $component,
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            );
        }

        return $functions;
    }
}
