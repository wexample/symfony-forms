<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorAccessGuard;
use Wexample\SymfonyForms\Tests\Fixtures\App\Controller\GuardedController;

/**
 * A form posted to a page is refused as the page would be: its controller's
 * `#[IsGranted]` are read before the form is processed.
 */
class FormProcessorAccessGuardTest extends TestCase
{
    public function testThePageRolesAreRequiredBeforeTheFormIsProcessed(): void
    {
        $user = $this->guard(['ROLE_USER']);
        $administrator = $this->guard(['ROLE_USER', 'ROLE_ADMIN']);

        $this->assertTrue($user->mayReachController(new ReflectionMethod(GuardedController::class, 'open')));
        $this->assertFalse($user->mayReachController(new ReflectionMethod(GuardedController::class, 'administered')));
        $this->assertTrue($administrator->mayReachController(new ReflectionMethod(GuardedController::class, 'administered')));
        $this->assertFalse($this->guard([])->mayReachController(new ReflectionMethod(GuardedController::class, 'open')));
    }

    public function testASubjectIsLeftToTheController(): void
    {
        $this->assertTrue($this->guard(['ROLE_USER'])->mayReachController(new ReflectionMethod(GuardedController::class, 'onASubject')));
    }

    /**
     * @param list<string> $held
     */
    private function guard(array $held): FormProcessorAccessGuard
    {
        $checker = $this->createStub(AuthorizationCheckerInterface::class);
        $checker->method('isGranted')->willReturnCallback(static fn (mixed $attribute) => in_array($attribute, $held, true));

        return new FormProcessorAccessGuard($checker);
    }
}
