<?php

namespace Wexample\SymfonyForms\Twig;

use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyForms\Service\FormRenderingService;

class FormExtension extends \Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension
{
    public function __construct(
        private readonly FormRenderingService $contextService
    )
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                FormRenderingService::FORM_TYPE_TEXT_INPUT,
                function (
                    Environment $twig,
                    array $context = []
                ) {
                    $context['type'] = 'text';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_TEXT_INPUT);

                    return $this->renderTemplate(
                        $twig,
                        '@WexampleSymfonyFormsBundle/components/text_input.html.twig',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                FormRenderingService::FORM_TYPE_HIDDEN_INPUT,
                function (
                    Environment $twig,
                    array $context = []
                ) {
                    $context['type'] = 'hidden';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_HIDDEN_INPUT);

                    return $this->renderTemplate(
                        $twig,
                        '@WexampleSymfonyFormsBundle/components/hidden_input.html.twig',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }
}
