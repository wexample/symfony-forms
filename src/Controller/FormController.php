<?php

namespace Wexample\SymfonyForms\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorPostHandler;

#[Route(name: 'form_')]
final class FormController
{
    public const ROUTE_PROCESSOR_SUBMIT = 'processor_submit';
    public const ROUTE_ENTITY_FORM_PROCESSOR_SUBMIT = 'entity_form_processor_submit';

    #[Route(
        path: 'system/form/{name}/submit',
        name: self::ROUTE_PROCESSOR_SUBMIT,
        methods: [Request::METHOD_POST]
    )]
    public function processorSubmit(
        FormProcessorPostHandler $formProcessorPostHandler,
        string $name,
        Request $request
    ): Response {
        return $formProcessorPostHandler->handleSubmission($name, $request);
    }

    #[Route(
        path: 'system/form/{name}/submit/entity/{id}',
        name: self::ROUTE_ENTITY_FORM_PROCESSOR_SUBMIT,
        defaults: ['id' => null],
        methods: [Request::METHOD_POST]
    )]
    public function entityProcessorSubmit(
        FormProcessorPostHandler $formProcessorPostHandler,
        string $name,
        Request $request
    ): Response {
        return $formProcessorPostHandler->handleSubmission($name, $request);
    }
}
