<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyHelpers\Helper\RequestHelper;

class FormProcessorPostHandler
{
    public function __construct(
        private readonly ContainerInterface $processors,
        private readonly AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function handleSubmission(
        string $formName,
        Request $request
    ): Response {
        $formClass = AbstractFormProcessor::FORMS_CLASS_BASE_PATH
            . ClassHelper::longTableizedNameToClass($formName);

        if (! class_exists($formClass)) {
            throw new RuntimeException('Form class not found: ' . $formClass);
        }

        $processorClass = ClassHelper::getClassCousin(
            $formClass,
            AbstractFormProcessor::FORMS_CLASS_BASE_PATH,
            '',
            AbstractFormProcessor::FORMS_PROCESSOR_CLASS_BASE_PATH,
            AbstractFormProcessor::CLASS_EXTENSION
        );

        if (! class_exists($processorClass)) {
            throw new RuntimeException('Form processor class not found: ' . $processorClass);
        }

        if (! $this->processors->has($processorClass)) {
            throw new RuntimeException('Form processor service not found: ' . $processorClass);
        }

        /** @var AbstractFormProcessor $formProcessor */
        $formProcessor = $this->processors->get($processorClass);
        $this->assertHasAccess($formProcessor);
        $form = $formProcessor->handleSubmission($request);

        if (RequestHelper::isJsonRequest($request)) {
            return new JsonResponse(
                $this->buildFormResponsePayload($formProcessor, $form)
            );
        }

        return $formProcessor->render($form);
    }

    private function assertHasAccess(AbstractFormProcessor $formProcessor): void
    {
        $roles = $formProcessor->getRequiredRoles();

        if (empty($roles)) {
            return;
        }

        foreach ($roles as $role) {
            if ($role === 'PUBLIC_ACCESS') {
                return;
            }

            if ($this->authorizationChecker->isGranted($role)) {
                return;
            }
        }

        throw new AccessDeniedHttpException('Access denied for form submission.');
    }

    private function buildFormResponsePayload(
        AbstractFormProcessor $formProcessor,
        FormInterface $form
    ): array {
        $errors = [
            'form' => [],
            'fields' => [],
            'count' => 0,
        ];
        $translationKeys = [];

        foreach ($form->getErrors(true, true) as $error) {
            $origin = $error->getOrigin();
            $message = $error->getMessage();

            if (! $origin || $origin === $form) {
                $errors['form'][] = $message;
                $translationKeys[] = $message;
                ++$errors['count'];

                continue;
            }

            $fullName = $this->buildFullFieldName($origin);

            if (! isset($errors['fields'][$fullName])) {
                $errors['fields'][$fullName] = [];
            }

            $errors['fields'][$fullName][] = $message;
            $translationKeys[] = $message;
            ++$errors['count'];
        }

        $payload = [
            'ok' => $errors['count'] === 0,
            'form' => [
                'name' => $form->getName(),
                'errors' => $errors,
            ],
        ];

        $translations = $formProcessor->translateKeys($translationKeys);

        if ($translations) {
            $payload['translations'] = $translations;
        }

        $redirectUrl = $formProcessor->getRedirectUrl();

        if (is_string($redirectUrl) && $redirectUrl !== '') {
            $payload['redirect'] = [
                'url' => $redirectUrl,
            ];
        }

        return $payload;
    }

    private function buildFullFieldName(FormInterface $field): string
    {
        $parts = [];
        $current = $field;

        while ($current) {
            $name = $current->getName();
            if ($name !== '') {
                array_unshift($parts, $name);
            }
            $current = $current->getParent();
        }

        $root = array_shift($parts) ?? '';
        $full = $root;

        foreach ($parts as $part) {
            $full .= '[' . $part . ']';
        }

        return $full;
    }
}
