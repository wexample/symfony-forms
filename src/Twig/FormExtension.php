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

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                FormRenderingService::FORM_TYPE_TEXT_INPUT,
                function (
                    Environment $twig,
                    $twigContext,
                    array $context = []
                ) {
                    $context['type'] = 'text';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_TEXT_INPUT);

                    return $this->renderComponent(
                        $twig,
                        $twigContext,
                        '@WexampleSymfonyDesignSystemBundle/components/form/text-input',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                FormRenderingService::FORM_TYPE_PASSWORD_INPUT,
                function (
                    Environment $twig,
                    $twigContext,
                    array $context = []
                ) {
                    $context['type'] = 'password';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_PASSWORD_INPUT);

                    return $this->renderComponent(
                        $twig,
                        $twigContext,
                        '@WexampleSymfonyDesignSystemBundle/components/form/password-input',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                FormRenderingService::FORM_TYPE_HIDDEN_INPUT,
                function (
                    Environment $twig,
                    $twigContext,
                    array $context = []
                ) {
                    $context['type'] = 'hidden';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_HIDDEN_INPUT);

                    return $this->renderComponent(
                        $twig,
                        $twigContext,
                        '@WexampleSymfonyDesignSystemBundle/components/form/hidden-input',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                FormRenderingService::FORM_TYPE_SUBMIT_INPUT,
                function (
                    Environment $twig,
                    $twigContext,
                    array $context = []
                ) {
                    $context['type'] = 'submit';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_SUBMIT_INPUT);

                    return $this->renderComponent(
                        $twig,
                        $twigContext,
                        '@WexampleSymfonyDesignSystemBundle/components/form/submit-input',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                FormRenderingService::FORM_TYPE_TEXTAREA_INPUT,
                function (
                    Environment $twig,
                    $twigContext,
                    array $context = []
                ) {
                    $context['type'] = 'textarea';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_TEXTAREA_INPUT);

                    return $this->renderComponent(
                        $twig,
                        $twigContext,
                        '@WexampleSymfonyDesignSystemBundle/components/form/textarea-input',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
            new TwigFunction(
                FormRenderingService::FORM_TYPE_SELECT_INPUT,
                function (
                    Environment $twig,
                    $twigContext,
                    array $context = []
                ) {
                    $context['type'] = 'select';
                    $this->contextService->validate($context, FormRenderingService::FORM_TYPE_SELECT_INPUT);

                    return $this->renderComponent(
                        $twig,
                        $twigContext,
                        '@WexampleSymfonyDesignSystemBundle/components/form/select-input',
                        $context
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }
}
