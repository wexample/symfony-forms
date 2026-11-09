<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wexample\SymfonyHelpers\Helper\RequestHelper;

class FormProcessorPostHandler
{
    public function __construct(
        private readonly FormProcessorRegistry $registry,
        private readonly FormProcessorAccessGuard $accessGuard,
        private readonly FormResponsePayloadBuilder $payloadBuilder
    ) {
    }

    public function handleSubmission(
        string $formName,
        Request $request
    ): Response {
        $formProcessor = $this->registry->findByFormName($formName);

        // A name no form answers to is an address that does not exist.
        if (! $formProcessor) {
            throw new NotFoundHttpException('Form processor not found for form name: ' . $formName);
        }

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

    private function assertHasAccess(AbstractFormProcessor $formProcessor): void
    {
        if (! $this->accessGuard->mayProcess($formProcessor)) {
            throw new AccessDeniedHttpException('Access denied for form submission.');
        }
    }
}
