<?php

namespace Wexample\SymfonyForms\Tests\Traits;

use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyLoader\Rendering\RenderNode\InitialLayoutRenderNode;
use Wexample\SymfonyLoader\Rendering\RenderPass;
use Wexample\SymfonyLoader\Service\AdaptiveRendererService;

/**
 * Renders a form through the package theme and the design-system components,
 * as a page does, for a kernel test case.
 */
trait RendersFormsTrait
{
    private const string RENDER_VIEW = 'test';

    private function renderForm(FormInterface $form): string
    {
        $twig = self::getContainer()->get('twig');
        // What `AdaptiveRendererService` does on a real page: the components
        // read the render pass as a twig global, not as a form variable, and
        // they write into the layout node it opens.
        $twig->addGlobal('render_pass', $this->renderPass());

        return $twig
            ->createTemplate('{{ form_widget(form) }}')
            ->render(['form' => $form->createView()]);
    }

    /**
     * The element carrying an id, whatever its tag.
     */
    private function tagWithId(
        string $html,
        string $id
    ): string {
        $pattern = '/<[a-z]+[^>]*\bid="' . preg_quote($id, '/') . '"[^>]*>/';

        $this->assertMatchesRegularExpression($pattern, $html, 'No element carries the id ' . $id);
        preg_match($pattern, $html, $matches);

        return $matches[0];
    }

    /**
     * A render pass built the way a page builds one, so the components are
     * given the registry, the usages and the layout node they read.
     */
    private function renderPass(): RenderPass
    {
        $container = self::getContainer();

        $renderPass = $container
            ->get(AdaptiveRendererService::class)
            ->createRenderPass(self::RENDER_VIEW);

        $layout = new InitialLayoutRenderNode('test');
        $renderPass->setLayoutRenderNode($layout);
        $layout->init($renderPass, self::RENDER_VIEW);

        return $renderPass;
    }
}
