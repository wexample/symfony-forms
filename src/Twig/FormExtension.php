<?php

namespace Wexample\SymfonyForms\Twig;

use Twig\Environment;
use Twig\TwigFunction;

class FormExtension extends \Wexample\SymfonyDesignSystem\Twig\AbstractTemplateExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'form_input',
                function (Environment $twig) {
                    return $this->renderTemplate(
                        $twig,
                        '@WexampleSymfonyForms/components/form_input.html.twig',
                        [

                        ]
                    );
                },
                self::TEMPLATE_FUNCTION_OPTIONS
            ),
        ];
    }
}
