<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyHelpers\Helper\RequestHelper;
use Wexample\SymfonyHelpers\Helper\RoleHelper;

class FormProcessorPostHandler
{
    public function __construct(
        private readonly ServiceLocator $processors,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly FormResponsePayloadBuilder $payloadBuilder
    ) {
    }

    public function handleSubmission(
        string $formName,
        Request $request
    ): Response {
        $processorClass = $this->resolveProcessorClass($formName);

        if (! $processorClass) {
            throw new RuntimeException('Form processor not found for form name: ' . $formName);
        }

        /** @var AbstractFormProcessor $formProcessor */
        $formProcessor = $this->processors->get($processorClass);
        $this->assertHasAccess($formProcessor);
        $form = $formProcessor->handleSubmission($request);

        $response = $formProcessor->handleSubmissionResponseFromForm($form);
        if ($response) {
            return $response;
        }

        if (RequestHelper::isJsonRequest($request)) {
            return new JsonResponse(
                $this->payloadBuilder->build($formProcessor, $form)
            );
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function resolveProcessorClass(string $formName): ?string
    {
        foreach (array_keys($this->processors->getProvidedServices()) as $processorClass) {
            /** @var class-string<AbstractFormProcessor> $processorClass */
            if (ClassHelper::longTableized($processorClass::getFormClass()) === $formName) {
                return $processorClass;
            }
        }

        return null;
    }

    private function assertHasAccess(AbstractFormProcessor $formProcessor): void
    {
        $roles = $formProcessor->getRequiredRoles();

        if (empty($roles)) {
            return;
        }

        foreach ($roles as $role) {
            if ($role === RoleHelper::PUBLIC_ACCESS) {
                return;
            }

            if ($this->authorizationChecker->isGranted($role)) {
                return;
            }
        }

        throw new AccessDeniedHttpException('Access denied for form submission.');
    }
}
