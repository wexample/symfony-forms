<?php

namespace Wexample\SymfonyForms\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorPostHandler;

#[Route(name: 'form_')]
final class FormController
{
    public const ROUTE_PATH_PROCESSOR_SUBMIT = '_forms/submit/{name}';
    public const ROUTE_PROCESSOR_SUBMIT = 'processor_submit';
    public const ROUTE_ENTITY_FORM_PROCESSOR_SUBMIT = 'entity_form_processor_submit';
    public const ROUTE_PATH_ENTITY_PROCESSOR_SUBMIT = '_forms/submit/{name}/entity/{id}';

    #[Route(
        path: self::ROUTE_PATH_PROCESSOR_SUBMIT,
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
        path: self::ROUTE_PATH_ENTITY_PROCESSOR_SUBMIT,
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
