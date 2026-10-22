<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\SymfonyForms\Service\FormProcessor\Notification;

class NotificationTest extends TestCase
{
    public function testWithoutActionItCarriesNone(): void
    {
        $this->assertSame(
            ['type' => 'success', 'message' => 'Saved.'],
            (new Notification('Saved.'))->toArray()
        );
    }

    public function testItsActionTravelsWithIt(): void
    {
        $action = ['label' => 'Open the cart', 'href' => '/cart/', 'target' => 'panel'];

        $this->assertSame(
            ['type' => 'success', 'message' => 'Added.', 'action' => $action],
            (new Notification('Added.', Notification::TYPE_SUCCESS, $action))->toArray()
        );
    }
}
