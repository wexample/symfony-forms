<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Form\Type\RadioInputType;
use Wexample\SymfonyForms\Form\Type\RangeInputType;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * What a simulation bar holds: one choice among a few as segments, and a
 * value along a scale — drawn by the design system, submitted as any field.
 */
class ScaleFieldsTest extends KernelTestCase
{
    use RendersFormsTrait;

    private const array WINDOWS = ['3 months' => '3', '6 months' => '6'];

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testASegmentedRadioGroupIsAStripOfTheSameRadios(): void
    {
        $html = $this->renderForm($this->form('window', RadioInputType::class, '6', [
            'choices' => self::WINDOWS,
            'auto_translate_choices' => false,
            'segmented' => true,
        ]));

        $this->assertStringContainsString('radio-group--segmented', $html);
        $this->assertSame(2, preg_match_all('/type="radio"/', $html));
    }

    public function testARadioGroupIsAListUnlessAsked(): void
    {
        $html = $this->renderForm($this->form('window', RadioInputType::class, '6', ['choices' => self::WINDOWS, 'auto_translate_choices' => false]));

        $this->assertStringNotContainsString('radio-group--segmented', $html);
    }

    public function testARangeCarriesItsScaleAndItsSuffix(): void
    {
        $html = $this->renderForm($this->form('coverage', RangeInputType::class, 1.2, [
            'min' => 0.5,
            'max' => 2,
            'step' => 0.05,
            'suffix' => 'x',
        ]));
        $input = $this->tagWithId($html, 'scale_coverage');

        $this->assertStringContainsString('type="range"', $input);
        $this->assertStringContainsString('min="0.5"', $input);
        $this->assertStringContainsString('max="2"', $input);
        $this->assertStringContainsString('step="0.05"', $input);
        $this->assertStringContainsString('value="1.2"', $input);
        $this->assertStringContainsString('range-input--suffix">x<', $html);
    }

    /**
     * Read with a point whatever the locale, as the browser sends it, and
     * kept as a figure.
     */
    public function testARangeSubmitsAFigure(): void
    {
        $form = $this->form('coverage', RangeInputType::class, null, ['min' => 0.5, 'max' => 2, 'step' => 0.05]);

        $form->submit(['coverage' => '1.35']);
        $this->assertSame(['coverage' => 1.35], $form->getData());
    }

    public function testAFrozenRangeShowsItsValue(): void
    {
        $html = $this->renderForm($this->form('buffer', RangeInputType::class, 2, ['suffix' => 'weeks', 'disabled' => true]));

        $this->assertStringNotContainsString('type="range"', $html);
        $this->assertStringContainsString('value="2 weeks"', $html);
    }

    private function form(string $name, string $type, mixed $value, array $options): FormInterface
    {
        return self::getContainer()
            ->get('form.factory')
            ->createNamedBuilder('scale', FormType::class, [$name => $value], ['translation_domain' => false])
            ->add($name, $type, $options + ['label' => 'Value'])
            ->getForm();
    }
}
