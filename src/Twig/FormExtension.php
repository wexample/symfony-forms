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
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_TEXT_INPUT);

                    return $this->renderTemplate(
                        $twig,
                        '@WexampleSymfonyFormsBundle/components/form_input.html.twig',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }
}
