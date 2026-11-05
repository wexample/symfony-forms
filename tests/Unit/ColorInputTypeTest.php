<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Wexample\SymfonyForms\Form\Type\ColorInputType;

class ColorInputTypeTest extends TestCase
{
    public function testWhatIsTypedIsReadAsAColour(): void
    {
        foreach (['#AABBCC' => '#aabbcc', 'abc' => '#aabbcc', ' #1f2 ' => '#11ff22', '' => null] as $typed => $expected) {
            $form = Forms::createFormFactoryBuilder()->getFormFactory()->create(ColorInputType::class);
            $form->submit($typed);

            $this->assertTrue($form->isSynchronized(), (string) $typed);
            $this->assertSame($expected, $form->getData(), (string) $typed);
        }
    }

    public function testWhatIsNotAColourIsRefused(): void
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()->create(ColorInputType::class);
        $form->submit('red');

        $this->assertFalse($form->isSynchronized());
    }

    public function testTheSwatchesAreReadWhateverTheirShape(): void
    {
        $vars = Forms::createFormFactoryBuilder()->getFormFactory()
            ->create(ColorInputType::class, null, ['swatches' => ['#F00', '#00ff00' => 'Green', ['value' => '00f', 'label' => 'Blue'], 'nope']])
            ->createView()->vars;

        $this->assertSame([
            ['value' => '#ff0000', 'label' => '#ff0000'],
            ['value' => '#00ff00', 'label' => 'Green'],
            ['value' => '#0000ff', 'label' => 'Blue'],
        ], $vars['swatches']);
    }
}
