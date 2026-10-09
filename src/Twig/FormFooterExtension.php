<?php

namespace Wexample\SymfonyForms\Twig;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormRendererInterface;
use Symfony\Component\Form\FormView;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Wexample\SymfonyForms\Form\Extension\FooterSubmitTypeExtension;

/**
 * `form_footer(form)`: the submit buttons a form keeps for its foot
 * (`in_footer`), drawn wherever the page puts them — its `page_actions`, which
 * a modal or a panel holds at its foot — and pointing at the form they send.
 *
 *     {% block page_actions %}{{ form_footer(form) }}{% endblock %}
 */
class FormFooterExtension extends AbstractExtension
{
    public function __construct(
        #[Autowire(service: 'twig.form.renderer')]
        private readonly FormRendererInterface $formRenderer,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('form_footer', $this->renderFooter(...), ['is_safe' => ['html']]),
        ];
    }

    public function renderFooter(FormView $form): string
    {
        $formId = self::getFormId($form);
        $html = '';

        foreach ($form->children as $child) {
            if (! ($child->vars[FooterSubmitTypeExtension::OPTION_IN_FOOTER] ?? false)) {
                continue;
            }

            // The type's own widget block, the unique `_name` ones aside: drawn
            // here whatever the body drew, which left it out.
            foreach (array_reverse($child->vars['block_prefixes']) as $prefix) {
                if (! str_starts_with($prefix, '_')) {
                    $html .= $this->formRenderer->renderBlock($child, $prefix . '_widget', [
                        'attr' => [...$child->vars['attr'], 'form' => $formId],
                    ]);
                    break;
                }
            }
        }

        return $html;
    }

    /**
     * The id the `<form>` tag carries, so a button outside it can name it.
     */
    public static function getFormId(FormView $form): string
    {
        return $form->vars['attr']['id'] ?? $form->vars['id'];
    }
}
