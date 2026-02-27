<?php

namespace Wexample\SymfonyForms\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Wexample\SymfonyForms\Attribute\FormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyHelpers\Helper\RouteHelper;

class FormProcessorRequestSubscriber implements EventSubscriberInterface
{
    public const string REQUEST_FORMS_ATTRIBUTE = '_form_processor_forms';

    public function __construct(
        private readonly ServiceLocator $processors,
        private readonly ServiceLocator $formDataResolvers
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onRequest', 5],
        ];
    }

    public function onRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $reflection = RouteHelper::resolveControllerMethodReflection($request);
        if (!$reflection) {
            return;
        }

        $attributes = $reflection->getAttributes(FormProcessor::class);

        if (empty($attributes)) {
            return;
        }

        $forms = [];

        foreach ($attributes as $attribute) {
            /** @var FormProcessor $config */
            $config = $attribute->newInstance();

            /** @var AbstractFormProcessor $processor */
            $processor = $this->processors->get($config->processorClass);
            $hasFormDataResolver = $config->formDataResolverClass !== null;
            $formData = $hasFormDataResolver
                ? $this->resolveFormData($config, $request)
                : null;

            if ($request->isMethod('POST')) {
                $form = $hasFormDataResolver
                    ? $processor->handleSubmissionWithData($request, $formData)
                    : $processor->handleSubmission($request);
                $response = $processor->handleSubmissionResponseFromForm($form);
                if ($response) {
                    $event->setResponse($response);
                    return;
                }
            } else {
                $form = $hasFormDataResolver
                    ? $processor->createForm($formData)
                    : $processor->createForm();
            }

            $forms[$config->formArgumentName] = $form;
        }

        $request->attributes->set(self::REQUEST_FORMS_ATTRIBUTE, $forms);
    }

    private function resolveFormData(
        FormProcessor $config,
        Request $request
    ): mixed {
        return $this->formDataResolvers->get($config->formDataResolverClass)->resolve(
            $request,
            $config->formDataResolverOptions
        );
    }

    public static function getFormFromRequest(
        RequestEvent $event,
        string $argumentName
    ): ?FormInterface {
        $forms = $event->getRequest()->attributes->get(self::REQUEST_FORMS_ATTRIBUTE, []);
        return $forms[$argumentName] ?? null;
    }
}
