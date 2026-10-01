<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Form\Type\FloatType;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * A unit or a currency beside the value: drawn by the design-system input,
 * announced with the field, and never part of what is submitted.
 */
class InputAddonTest extends KernelTestCase
{
    use RendersFormsTrait;

    private const array TYPES = [
        TextInputType::class,
        NumberInputType::class,
        FloatType::class,
    ];

    private const string ADDON_DOMAIN = 'measure_form';

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testASuffixIsDrawnBesideTheValueAndAnnouncedWithIt(): void
    {
        foreach (self::TYPES as $type) {
            $html = $this->renderForm($this->form($type, ['suffix' => 'kg']));

            $this->assertMatchesRegularExpression(
                '/<span class="form--input-addon" id="measure_value-suffix">kg<\/span>/',
                $html,
                $type
            );
            $this->assertStringContainsString(
                'aria-describedby="measure_value-suffix"',
                $this->tagWithId($html, 'measure_value'),
                $type
            );
        }
    }

    public function testAPrefixIsDrawnBeforeTheValue(): void
    {
        $html = $this->renderForm($this->form(NumberInputType::class, ['prefix' => '€']));

        $this->assertMatchesRegularExpression(
            '/id="measure_value-prefix">€<\/span>\s*<input/',
            $html
        );
    }

    public function testAFieldWithoutAddonIsDrawnAsBefore(): void
    {
        $html = $this->renderForm($this->form(NumberInputType::class));

        $this->assertStringNotContainsString('form--input-addon', $html);
        $this->assertStringNotContainsString('aria-describedby', $html);
    }

    /**
     * The addon is printed, not posted: the only field in the form is the
     * value, and the value is the whole of the data.
     */
    public function testTheSubmittedDataHoldsTheValueOnly(): void
    {
        $form = $this->form(NumberInputType::class, ['suffix' => 'kg']);

        $html = $this->renderForm($form);
        $this->assertSame(1, preg_match_all('/name="measure\[/', $html));

        $form->submit(['value' => '72.5']);
        $this->assertSame(['value' => 72.5], $form->getData());
    }

    public function testAFrozenFieldKeepsItsAddon(): void
    {
        foreach (self::TYPES as $type) {
            $html = $this->renderForm($this->form($type, ['suffix' => 'kg', 'disabled' => true]));

            $this->assertStringContainsString('id="measure_value-suffix">kg<', $html, $type);
            $this->assertStringContainsString('readonly', $this->tagWithId($html, 'measure_value'), $type);
        }
    }

    /**
     * Translated as a label is: a key in the field's domain gives its text.
     */
    public function testAnAddonIsTranslatedThroughTheFieldDomain(): void
    {
        $translator = self::getContainer()->get('translator');
        // The catalogue is filled from the front paths on first use; the key
        // is added after, so that filling does not replace it.
        $translator->trans('unit.kilogram', [], self::ADDON_DOMAIN);
        $translator->getCatalogue('en')->add(['unit.kilogram' => 'kg'], self::ADDON_DOMAIN);

        $html = $this->renderForm(
            $this->form(NumberInputType::class, ['suffix' => 'unit.kilogram'], self::ADDON_DOMAIN)
        );

        $this->assertStringContainsString('id="measure_value-suffix">kg<', $html);
    }

    private function form(
        string $type,
        array $options = [],
        string|false $translationDomain = false
    ): FormInterface {
        return self::getContainer()
            ->get('form.factory')
            ->createNamedBuilder('measure', FormType::class, ['value' => null], [
                'translation_domain' => $translationDomain,
            ])
            ->add('value', $type, $options + ['label' => 'Value'])
            ->getForm();
    }
}
