<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\RecordForm;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Record;

/**
 * What a frozen field guarantees, which is not a rendering: a post carrying a
 * value for it changes nothing. The browser is free to send the field — a
 * `readonly` input is submitted like any other — so the guarantee has to hold
 * on the server, and it is the one thing an attacker cannot route around.
 */
class FrozenFieldSubmitTest extends KernelTestCase
{
    /**
     * A payload claiming a new value for every single field of the form.
     */
    private const array FORGED = [
        'name' => 'Forged',
        'email' => 'forged@example.com',
        'site' => 'https://forged.example.com',
        'notes' => 'Forged',
        'password' => 'forged',
        'weight' => '99.5',
        'count' => '99',
        'dateBorn' => '2000-01-01',
        'dateSeen' => '2000-01-01T10:00',
        'timeSeen' => '10:00',
        'status' => 'archived',
        'kind' => 'second',
        'active' => '1',
        'code' => '999999',
        'mood' => ':-(',
        'dateCreated' => 'Forged',
        'dateActivated' => 'Forged',
    ];

    public function testAForgedPostLeavesEveryFrozenValueUntouched(): void
    {
        $record = $this->record();

        $this->form(true, $record)->submit(self::FORGED);

        $this->assertSame('Jane', $record->name);
        $this->assertSame('jane@example.com', $record->email);
        $this->assertSame('https://jane.example.com', $record->site);
        $this->assertSame('Seen in March.', $record->notes);
        $this->assertSame('kept', $record->password);
        $this->assertSame(62.4, $record->weight);
        $this->assertSame(3.0, $record->count);
        $this->assertSame('1981-07-02', $record->dateBorn->format('Y-m-d'));
        $this->assertSame('2026-03-12 09:30', $record->dateSeen->format('Y-m-d H:i'));
        $this->assertSame('09:30', $record->timeSeen->format('H:i'));
        $this->assertSame('active', $record->status);
        $this->assertSame('first', $record->kind);
        $this->assertTrue($record->active);
        $this->assertSame('123456', $record->code);
        $this->assertSame(':-)', $record->mood);
    }

    /**
     * The same payload, on the same form class, with nothing frozen. Without
     * this the test above would pass just as well on a form that reads no
     * input at all.
     */
    public function testTheSamePostIsTakenWhenNothingIsFrozen(): void
    {
        $record = $this->record();

        $this->form(false, $record)->submit(self::FORGED);

        $this->assertSame('Forged', $record->name);
        $this->assertSame('forged@example.com', $record->email);
        $this->assertSame(99.5, $record->weight);
        $this->assertSame('2000-01-01', $record->dateBorn->format('Y-m-d'));
        $this->assertSame('archived', $record->status);
        $this->assertSame('second', $record->kind);
        $this->assertSame(':-(', $record->mood);
    }

    /**
     * A display value is not a field: it is unmapped, so even a post naming it
     * writes nowhere, and there is no property for it to write to.
     */
    public function testADisplayValueIsNotBoundToTheModel(): void
    {
        $form = $this->form(false, $this->record());
        $form->submit(self::FORGED);

        $this->assertFalse($form->get('dateCreated')->getConfig()->getMapped());
        $this->assertSame('12 March 2026', $form->get('dateCreated')->getData());
    }

    private function form(
        bool $frozen,
        Record $record
    ): FormInterface {
        return self::getContainer()
            ->get('form.factory')
            ->create(RecordForm::class, $record, ['frozen' => $frozen]);
    }

    private function record(): Record
    {
        $record = new Record();
        $record->name = 'Jane';
        $record->email = 'jane@example.com';
        $record->site = 'https://jane.example.com';
        $record->notes = 'Seen in March.';
        $record->password = 'kept';
        $record->weight = 62.4;
        $record->count = 3.0;
        $record->dateBorn = new \DateTime('1981-07-02');
        $record->dateSeen = new \DateTime('2026-03-12 09:30');
        $record->timeSeen = new \DateTime('2026-03-12 09:30');
        $record->status = 'active';
        $record->kind = 'first';
        $record->active = true;
        $record->code = '123456';
        $record->mood = ':-)';

        return $record;
    }
}
