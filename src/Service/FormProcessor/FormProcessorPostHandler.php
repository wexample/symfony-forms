<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use RuntimeException;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Helper\FormHelper;
use Wexample\SymfonyHelpers\Helper\RequestHelper;
use Wexample\SymfonyHelpers\Helper\RoleHelper;
use Wexample\SymfonyTranslations\Translation\Translator;

class FormProcessorPostHandler
{
    public function __construct(
        private readonly ServiceLocator $processors,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly Translator $translator
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

        if (RequestHelper::isJsonRequest($request)) {
            return new JsonResponse(
                $this->buildFormResponsePayload($formProcessor, $form)
            );
        }

        $response = $formProcessor->handleSubmissionResponseFromForm($form);
        if ($response) {
            return $response;
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

            $fullName = FormHelper::buildFullFieldName($origin);

            if (! isset($errors['fields'][$fullName])) {
                $errors['fields'][$fullName] = [];
            }

            $errors['fields'][$fullName][] = $message;
            $translationKeys[] = $message;
            ++$errors['count'];
        }

        $payload = FormResponsePayload::fromForm($form)
            ->setErrors($errors);

        $translations = $this->translateKeys($translationKeys, $formProcessor, $form);

        if ($translations) {
            $payload->setTranslations($translations);
        }

        $action = $formProcessor->getSuccessAction()
            ?: ['type' => AbstractFormProcessor::ACTION_DEFAULT];
        $payload->setAction($action);

        return $payload->toArray();
    }

    private function translateKeys(
        array $keys,
        AbstractFormProcessor $formProcessor,
        FormInterface $form
    ): array {
        $translations = [];
        $translationDomain = $form->getConfig()->getOption('translation_domain')
            ?? AbstractForm::transTypeDomain($formProcessor::getFormClass());
        $this->translator->setDomain(
            Translator::DOMAIN_TYPE_FORM,
            $translationDomain
        );

        foreach ($keys as $key) {
            $translations[$key] = $this->translator->trans($key);
        }

        $this->translator->revertDomain(Translator::DOMAIN_TYPE_FORM);

        return $translations;
    }

}
