<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use ReflectionMethod;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Wexample\SymfonyHelpers\Helper\RoleHelper;

/**
 * Whether a form may be submitted: the roles its processor requires, and —
 * submitted to a page — the `#[IsGranted]` of that page's controller. A form
 * is processed on `kernel.request`, before Symfony reads those attributes on
 * the controller: unchecked here, a refused page would still save.
 */
class FormProcessorAccessGuard
{
    public function __construct(
        private readonly AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    /**
     * One of the processor's roles is held, or it requires none.
     */
    public function mayProcess(AbstractFormProcessor $processor): bool
    {
        $roles = $processor->getRequiredRoles();

        if (empty($roles)) {
            return true;
        }

        foreach ($roles as $role) {
            if ($role === RoleHelper::PUBLIC_ACCESS || $this->authorizationChecker->isGranted($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Every `#[IsGranted]` of the controller's class and method is held. One
     * naming a subject is left to the controller: its arguments are not
     * resolved yet; a page reading a subject checks it in its processor.
     */
    public function mayReachController(ReflectionMethod $method): bool
    {
        $attributes = [
            ...$method->getDeclaringClass()->getAttributes(IsGranted::class),
            ...$method->getAttributes(IsGranted::class),
        ];

        foreach ($attributes as $attribute) {
            /** @var IsGranted $isGranted */
            $isGranted = $attribute->newInstance();

            if ($isGranted->subject === null && ! $this->authorizationChecker->isGranted($isGranted->attribute)) {
                return false;
            }
        }

        return true;
    }
}
