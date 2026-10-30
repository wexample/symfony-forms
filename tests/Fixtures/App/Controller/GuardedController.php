<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Pages refused to some: the class to a role, a method to another, one
 * method to a subject only its arguments name.
 */
#[IsGranted('ROLE_USER')]
class GuardedController
{
    public function open(): void
    {
    }

    #[IsGranted('ROLE_ADMIN')]
    public function administered(): void
    {
    }

    #[IsGranted('edit', subject: 'record')]
    public function onASubject(): void
    {
    }
}
