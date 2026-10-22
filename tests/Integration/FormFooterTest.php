<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Wexample\SymfonyForms\Form\Type\SubmitInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * A submit kept for the foot of the modal or the page: out of the form's body,
 * drawn by `form_footer(form)`, and still sending the form.
 */
class FormFooterTest extends KernelTestCase
{
    use RendersFormsTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testASubmitInFooterLeavesTheBodyForTheFoot(): void
    {
        $view = $this->form(inFooter: true)->createView();
        $twig = self::getContainer()->get('twig');
        $twig->addGlobal('render_pass', $this->renderPass());

        $body = $twig->createTemplate('{{ form_start(form) }}{{ form_widget(form) }}{{ form_end(form) }}')->render(['form' => $view]);
        $footer = $twig->createTemplate('{{ form_footer(form) }}')->render(['form' => $view]);

        $this->assertStringContainsString('<form', $body);
        $this->assertMatchesRegularExpression('/<form[^>]*\bid="note"/', $body);
        $this->assertStringNotContainsString('type="submit"', $body);

        $this->assertStringContainsString('type="submit"', $footer);
        $this->assertStringContainsString('form="note"', $footer);
        $this->assertStringContainsString('name="note[save]"', $footer);
    }

    public function testASubmitStaysInTheBodyByDefault(): void
    {
        $view = $this->form(inFooter: false)->createView();
        $twig = self::getContainer()->get('twig');
        $twig->addGlobal('render_pass', $this->renderPass());

        $body = $twig->createTemplate('{{ form_start(form) }}{{ form_widget(form) }}{{ form_end(form) }}')->render(['form' => $view]);

        $this->assertStringContainsString('type="submit"', $body);
        $this->assertDoesNotMatchRegularExpression('/<form[^>]*\bid=/', $body);
        $this->assertSame('', $twig->createTemplate('{{ form_footer(form) }}')->render(['form' => $view]));
    }

    private function form(bool $inFooter)
    {
        return self::getContainer()->get('form.factory')
            ->createNamedBuilder('note', FormType::class)
            ->add('title', TextInputType::class, ['label' => false])
            ->add('save', SubmitInputType::class, ['label' => false, 'in_footer' => $inFooter])
            ->getForm();
    }
}
