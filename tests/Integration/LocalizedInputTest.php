<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Form\Type\DateInputType;
use Wexample\SymfonyForms\Form\Type\FloatType;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * Numbers and dates as a reader outside the server's locale and time zone
 * types them: a decimal comma, a day that must stay the day typed.
 */
class LocalizedInputTest extends KernelTestCase
{
    use RendersFormsTrait;

    private const array NUMBER_TYPES = [
        FloatType::class,
        NumberInputType::class,
    ];

    private string $locale;

    private string $timezone;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->locale = \Locale::getDefault();
        $this->timezone = date_default_timezone_get();
        \Locale::setDefault('fr');
    }

    protected function tearDown(): void
    {
        \Locale::setDefault($this->locale);
        date_default_timezone_set($this->timezone);

        parent::tearDown();
    }

    public function testADecimalCommaIsReadAsTheDecimalSeparator(): void
    {
        foreach (self::NUMBER_TYPES as $type) {
            $this->assertSame(72.5, $this->submitted($type, '72,5'), $type);
        }
    }

    /**
     * Never refused, never read as a thousands separator.
     */
    public function testADecimalPointIsAcceptedToo(): void
    {
        foreach (self::NUMBER_TYPES as $type) {
            $this->assertSame(72.5, $this->submitted($type, '72.5'), $type);
        }
    }

    public function testANumberIsShownBackWithTheLocaleSeparator(): void
    {
        foreach (self::NUMBER_TYPES as $type) {
            $view = $this->form($type, 72.5)->createView();

            $this->assertSame('72,5', $view['value']->vars['value'], $type);
        }
    }

    /**
     * A Doctrine `DECIMAL` is a PHP string: `input: string` and the column's
     * scale keep `72.30` from going through a float and back as `72.3`.
     */
    public function testADecimalColumnRoundTripsAsAString(): void
    {
        $options = ['input' => 'string', 'scale' => 2];

        $form = $this->form(NumberInputType::class, '72.30', $options);
        $this->assertSame('72,30', $form->createView()['value']->vars['value']);

        $form->submit(['value' => '72,3']);
        $this->assertSame('72.30', $form->get('value')->getData());
    }

    /**
     * `<input type="number">` holds no comma: a French value written into one
     * shows an empty field, and a browser refuses the comma typed in it. The
     * localized rendering is a text input asking for a decimal keyboard.
     */
    public function testALocalizedNumberIsRenderedAsTextWithADecimalKeyboard(): void
    {
        foreach (self::NUMBER_TYPES as $type) {
            $tag = $this->tagWithId($this->renderForm($this->form($type, 72.5)), 'measure_value');

            $this->assertStringContainsString('type="text"', $tag, $type);
            $this->assertStringContainsString('inputmode="decimal"', $tag, $type);
            $this->assertStringContainsString('value="72,5"', $tag, $type);
        }
    }

    /**
     * `html5: true` is the other choice: the browser's own number field, which
     * reads and writes a point whatever the locale, and takes any decimal.
     */
    public function testAnHtml5NumberIsANumberFieldThatTakesDecimals(): void
    {
        foreach (self::NUMBER_TYPES as $type) {
            $tag = $this->tagWithId(
                $this->renderForm($this->form($type, 72.5, ['html5' => true])),
                'measure_value'
            );

            $this->assertStringContainsString('type="number"', $tag, $type);
            $this->assertStringContainsString('step="any"', $tag, $type);
            $this->assertStringContainsString('value="72.5"', $tag, $type);
        }
    }

    /**
     * A date field sends a plain `YYYY-MM-DD`, so the browser's zone plays no
     * part; the server's must not either.
     */
    public function testTheDayTypedIsTheDayStoredWhateverTheServerZone(): void
    {
        foreach (['Pacific/Kiritimati', 'UTC', 'Pacific/Pago_Pago'] as $timezone) {
            date_default_timezone_set($timezone);

            $form = $this->form(DateInputType::class, null);
            $form->submit(['value' => '1981-07-03']);
            $this->assertSame('1981-07-03', $form->get('value')->getData()->format('Y-m-d'), $timezone);

            // As Doctrine hydrates a `date` column: midnight, in the server zone.
            $view = $this->form(DateInputType::class, new \DateTime('1981-07-03'))->createView();
            $this->assertSame('1981-07-03', $view['value']->vars['value'], $timezone);
        }
    }

    /**
     * An application showing times in the reader's zone sets `view_timezone`;
     * on a day with no time it would move the day by the gap between the two
     * zones, so a date field ignores it.
     */
    public function testAViewTimezoneDoesNotMoveTheDay(): void
    {
        $options = ['model_timezone' => 'UTC', 'view_timezone' => 'Pacific/Kiritimati'];

        $form = $this->form(DateInputType::class, null, $options);
        $form->submit(['value' => '1981-07-03']);
        $this->assertSame('1981-07-03', $form->get('value')->getData()->format('Y-m-d'));

        $view = $this->form(
            DateInputType::class,
            new \DateTime('1981-07-03', new \DateTimeZone('UTC')),
            ['model_timezone' => 'UTC', 'view_timezone' => 'Pacific/Pago_Pago']
        )->createView();
        $this->assertSame('1981-07-03', $view['value']->vars['value']);
    }

    private function submitted(
        string $type,
        string $value
    ): mixed {
        $form = $this->form($type, null);
        $form->submit(['value' => $value]);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));

        return $form->get('value')->getData();
    }

    private function form(
        string $type,
        mixed $value,
        array $options = []
    ): FormInterface {
        return self::getContainer()
            ->get('form.factory')
            ->createNamedBuilder('measure', FormType::class, ['value' => $value], [
                'translation_domain' => false,
            ])
            ->add('value', $type, $options + ['label' => 'Value'])
            ->getForm();
    }
}
