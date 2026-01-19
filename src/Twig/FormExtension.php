<?php

namespace Wexample\SymfonyForms\Twig;

use Twig\Environment;
use Twig\TwigFunction;
use Wexample\SymfonyForms\Service\FormRenderingService;

class FormExtension extends \Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension
{
    public function __construct(
        private readonly FormRenderingService $contextService
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'form_input',
                function (Environment $twig, array $context = []) {
                    $context = $this->contextService->prepare($context);

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
