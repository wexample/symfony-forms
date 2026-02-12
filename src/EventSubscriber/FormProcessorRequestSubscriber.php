<?php

namespace Wexample\SymfonyForms\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Wexample\SymfonyForms\Attribute\FormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;

class FormProcessorRequestSubscriber implements EventSubscriberInterface
{
    public const string REQUEST_FORMS_ATTRIBUTE = '_form_processor_forms';

    public function __construct(
        private readonly ServiceLocator $processors
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

            if ($request->isMethod('POST')) {
                $form = $processor->handleSubmission($request);

                if ($form->isSubmitted() && $form->isValid()) {
                    $action = $processor->getSuccessAction();
                    if (is_array($action)
                        && ($action['type'] ?? null) === AbstractFormProcessor::ACTION_REDIRECT
                        && !empty($action['url'])
                    ) {
                        $event->setResponse(new RedirectResponse((string) $action['url']));
                        return;
                    }
                }
            } else {
                $form = $processor->createForm();
            }

            $forms[$config->formArgumentName] = $form;
        }

        $request->attributes->set(self::REQUEST_FORMS_ATTRIBUTE, $forms);
    }

    public static function getFormFromRequest(
        RequestEvent $event,
        string $argumentName
    ): ?FormInterface {
        $forms = $event->getRequest()->attributes->get(self::REQUEST_FORMS_ATTRIBUTE, []);
        return $forms[$argumentName] ?? null;
    }
}
