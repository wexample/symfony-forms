<?php

declare(strict_types=1);

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Wexample\SymfonyForms\Service\FormRenderingService;
use Wexample\SymfonyForms\Twig\FormExtension;
use Wexample\SymfonyLoader\Rendering\RenderPass;
use Wexample\SymfonyLoader\Twig\ComponentsExtension;

/**
 * The field functions templates call by name: each sets its `type`, checks
 * the context against the schema of its own name and hands the component of
 * the design system that draws it.
 */
class FormExtensionTest extends TestCase
{
    private const array EXPECTED = [
        'text_input' => ['text', 'text-input'],
        'password_input' => ['password', 'password-input'],
        'hidden_input' => ['hidden', 'hidden-input'],
        'submit_input' => ['submit', 'submit-input'],
        'textarea_input' => ['textarea', 'textarea-input'],
        'select_input' => ['select', 'select-input'],
    ];

    public function testEachFieldDrawsItsComponentWithItsType(): void
    {
        $validated = [];
        $rendered = [];

        $validator = $this->createStub(FormRenderingService::class);
        $validator->method('validate')->willReturnCallback(function (array $context, string $type) use (&$validated): void {
            $validated[] = [$type, $context['type']];
        });

        $components = $this->createStub(ComponentsExtension::class);
        $components->method('component')->willReturnCallback(
            function (Environment $twig, RenderPass $renderPass, string $path, array $options, array $vars) use (&$rendered): string {
                $rendered[] = [$path, $vars['type'], $vars['name']];

                return $path;
            }
        );

        $extension = new FormExtension($components, $validator);
        $twig = $this->createStub(Environment::class);
        $twigContext = ['render_pass' => $this->createStub(RenderPass::class)];
        $names = [];

        foreach ($extension->getFunctions() as $function) {
            $names[] = $function->getName();
            $this->assertTrue($function->needsContext());
            $this->assertTrue($function->needsEnvironment());

            ($function->getCallable())($twig, $twigContext, ['name' => 'field']);
        }

        $this->assertSame(array_keys(self::EXPECTED), $names);

        $expectedValidated = [];
        $expectedRendered = [];

        foreach (self::EXPECTED as $name => [$type, $component]) {
            $expectedValidated[] = [$name, $type];
            $expectedRendered[] = ['@WexampleSymfonyDesignSystemBundle/components/form/'.$component, $type, 'field'];
        }

        $this->assertSame($expectedValidated, $validated);
        $this->assertSame($expectedRendered, $rendered);
    }
}
