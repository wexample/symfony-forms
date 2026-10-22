<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Form\Type\WeekInputType;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * A week travels as its key, `2026-W29`: what the browser posts, what the form
 * hands the model, what php-date reads.
 */
class WeekInputTest extends KernelTestCase
{
    use RendersFormsTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testTheWeekPostedIsTheWeekKeyStored(): void
    {
        $form = $this->form(null);
        $form->submit(['value' => '2026-W29']);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
        $this->assertSame('2026-W29', $form->get('value')->getData());
    }

    public function testAnArrayModelStaysAvailable(): void
    {
        $form = $this->form(null, ['input' => 'array']);
        $form->submit(['value' => '2026-W29']);

        $this->assertSame(['year' => 2026, 'week' => 29], $form->get('value')->getData());
    }

    public function testItIsDrawnAsAWeekFieldHoldingItsKey(): void
    {
        $tag = $this->tagWithId($this->renderForm($this->form('2026-W29')), 'measure_value');

        $this->assertStringContainsString('type="week"', $tag);
        $this->assertStringContainsString('value="2026-W29"', $tag);
    }

    private function form(
        mixed $value,
        array $options = []
    ): FormInterface {
        return self::getContainer()
            ->get('form.factory')
            ->createNamedBuilder('measure', FormType::class, ['value' => $value], [
                'translation_domain' => false,
            ])
            ->add('value', WeekInputType::class, $options + ['label' => 'Value'])
            ->getForm();
    }
}
