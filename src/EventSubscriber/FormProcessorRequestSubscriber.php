<?php

namespace Wexample\SymfonyForms\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Wexample\SymfonyForms\Attribute\FormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorDataResolverInterface;

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
        $controller = $request->attributes->get('_controller');

        if (!is_string($controller) || !str_contains($controller, '::')) {
            return;
        }

        [$class, $method] = explode('::', $controller, 2);
        if (!class_exists($class) || !method_exists($class, $method)) {
            return;
        }

        $reflection = new \ReflectionMethod($class, $method);
        $attributes = $reflection->getAttributes(FormProcessor::class);

        if (empty($attributes)) {
            return;
        }

        $forms = [];

        foreach ($attributes as $attribute) {
            /** @var FormProcessor $config */
            $config = $attribute->newInstance();

            if (!$this->processors->has($config->processorClass)) {
                throw new BadRequestHttpException(
                    sprintf('Unknown form processor "%s".', $config->processorClass)
                );
            }

            /** @var AbstractFormProcessor $processor */
            $processor = $this->processors->get($config->processorClass);
            $formData = $this->resolveFormData($config, $request);

            if ($request->isMethod('POST')) {
                $form = $config->formDataResolverClass
                    ? $processor->handleSubmissionWithData($request, $formData)
                    : $processor->handleSubmission($request);
                $response = $processor->handleSubmissionResponseFromForm($form);
                if ($response) {
                    $event->setResponse($response);
                    return;
                }
            } else {
                $form = $config->formDataResolverClass
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
        $resolverClass = $config->formDataResolverClass;
        if (! is_string($resolverClass) || $resolverClass === '') {
            return null;
        }

        if (! $this->formDataResolvers->has($resolverClass)) {
            throw new BadRequestHttpException(
                sprintf('Unknown form data resolver "%s".', $resolverClass)
            );
        }

        $resolver = $this->formDataResolvers->get($resolverClass);
        if (! $resolver instanceof FormProcessorDataResolverInterface) {
            throw new BadRequestHttpException(
                sprintf(
                    'Form data resolver "%s" must implement %s.',
                    $resolverClass,
                    FormProcessorDataResolverInterface::class
                )
            );
        }

        return $resolver->resolve(
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
