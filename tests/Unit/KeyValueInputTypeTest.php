<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Wexample\SymfonyForms\Form\Type\KeyValueInputType;

class KeyValueInputTypeTest extends TestCase
{
    public function testThePairsPostedAreReadAsARecordInTheirOrder(): void
    {
        $form = $this->create();

        $form->submit([
            ['key' => ' lang ', 'value' => 'fr'],
            ['key' => 'channel', 'value' => 'web'],
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame(['lang' => 'fr', 'channel' => 'web'], $form->getData());
    }

    public function testALineLeftBlankIsDropped(): void
    {
        $form = $this->create();

        $form->submit([['key' => 'lang', 'value' => 'fr'], ['key' => '', 'value' => '  ']]);

        $this->assertSame(['lang' => 'fr'], $form->getData());
    }

    public function testNothingWrittenIsNoRecord(): void
    {
        $form = $this->create(['lang' => 'fr']);

        $form->submit('');

        $this->assertTrue($form->isSynchronized());
        $this->assertNull($form->getData());
    }

    public function testAKeyWrittenTwiceIsRefusedNotDropped(): void
    {
        $form = $this->create();

        $form->submit([['key' => 'lang', 'value' => 'fr'], ['key' => 'lang', 'value' => 'en']]);

        $this->assertFalse($form->isSynchronized());
    }

    public function testAValueWithoutItsKeyIsRefused(): void
    {
        $form = $this->create();

        $form->submit([['key' => '', 'value' => 'fr']]);

        $this->assertFalse($form->isSynchronized());
    }

    public function testTheRecordIsShownAsPairsItsValuesAsText(): void
    {
        $form = $this->create(['lang' => 'fr', 'priority' => 2, 'urgent' => true]);

        $this->assertSame([
            ['key' => 'lang', 'value' => 'fr'],
            ['key' => 'priority', 'value' => '2'],
            ['key' => 'urgent', 'value' => 'true'],
        ], $form->createView()->vars['value']);
    }

    private function create(?array $data = null): FormInterface
    {
        return Forms::createFormFactoryBuilder()->getFormFactory()->create(KeyValueInputType::class, $data);
    }
}
