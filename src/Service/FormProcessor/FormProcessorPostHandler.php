<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyHelpers\Helper\RequestHelper;

class FormProcessorPostHandler
{
    public function __construct(
        private readonly ServiceLocator $processors,
        private readonly FormProcessorAccessGuard $accessGuard,
        private readonly FormResponsePayloadBuilder $payloadBuilder
    ) {
    }

    public function handleSubmission(
        string $formName,
        Request $request
    ): Response {
        $processorClass = $this->resolveProcessorClass($formName);

        // A name no form answers to is an address that does not exist.
        if (! $processorClass) {
            throw new NotFoundHttpException('Form processor not found for form name: ' . $formName);
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
        if (! $this->accessGuard->mayProcess($formProcessor)) {
            throw new AccessDeniedHttpException('Access denied for form submission.');
        }
    }
}
