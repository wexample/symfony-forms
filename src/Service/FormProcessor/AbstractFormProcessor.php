<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use RuntimeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyHelpers\Helper\RequestHelper;
use Wexample\SymfonyTranslations\Translation\Translator;

abstract class AbstractFormProcessor
{
    public const string CLASS_EXTENSION = 'Processor';
    public const string FORM_SUBMIT_ROUTE = 'form_processor_submit';
    public const string FORMS_CLASS_BASE_PATH = 'App\\Form\\';
    public const string FORMS_PROCESSOR_CLASS_BASE_PATH = 'App\\Service\\FormProcessor\\';
    public const string VAR_FORM_DATA = 'formData';
    private const string REQUEST_REDIRECT_PARAM = 'redirect';
    private const string SESSION_REDIRECT_TARGET = 'app.redirect_target';
    private const string SESSION_SECURITY_TARGET = '_security.main.target_path';
    public const string ACTION_REDIRECT = 'redirect';
    public const string ACTION_RELOAD = 'reload';
    public const string ACTION_NO_ACTION = 'no_action';

    protected ?Request $request = null;
    protected ?Translator $translator = null;
    protected ?array $successAction = null;

    public function __construct(
        protected FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        protected UrlGeneratorInterface $urlGenerator,
        ?Translator $translator = null
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->translator = $translator;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        $formClass = static::getFormClass();

        if (! class_exists($formClass)) {
            throw new RuntimeException(
                sprintf(
                    'Unable to find form %s related to processor %s.',
                    $formClass,
                    static::class
                )
            );
        }

        if (!isset($options['action'])) {
            $action = $this->createFormAction($data);
            if ($action) {
                $options['action'] = $action;
            }
        }

        return $this->formFactory->create(
            $formClass,
            $data,
            $options
        );
    }

    /**
     * Return the form class name matching with this processor.
     * By default, search the form which have the same name as the current
     * processor.
     */
    public static function getFormClass(): string
    {
        $formClass = static::guessFormClass();

        if (! $formClass || ! class_exists($formClass)) {
            throw new RuntimeException(sprintf(
                'Unable to resolve form class for %s. Override getFormClass().',
                static::class
            ));
        }

        return $formClass;
    }

    public function createFormAction($data): ?string
    {
        $formClass = static::getFormClass();
        $ajaxEnabled = property_exists($formClass, 'ajax') && $formClass::$ajax;

        if ($ajaxEnabled) {
            return $this->urlGenerator->generate(
                $this->getFormActionRoute(),
                $this->getFormActionArgs($data)
            );
        }

        if ($this->request) {
            return $this->request->getRequestUri();
        }

        return null;
    }

    public function getFormActionRoute(): string
    {
        return self::FORM_SUBMIT_ROUTE;
    }

    public function getFormActionArgs($data): array
    {
        return [
            'name' => ClassHelper::longTableized(static::getFormClass()),
        ];
    }

    public function formIsSubmitted(FormInterface $form): bool
    {
        return $form->isSubmitted();
    }

    public function handleSubmission(
        Request $request
    ): FormInterface {
        $form = $this->createFormSubmitted($request);

        $form->handleRequest($request);

        $this->processSubmittedForm($form);

        return $form;
    }

    public function handleSubmissionWithData(
        Request $request,
        $data = null
    ): FormInterface {
        $form = $this->createForm($data);

        $form->handleRequest($request);

        $this->processSubmittedForm($form);

        return $form;
    }

    public function handleSubmissionResponse(Request $request, $data = null): ?Response
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            return null;
        }

        $form = $data !== null
            ? $this->handleSubmissionWithData($request, $data)
            : $this->handleSubmission($request);

        return $this->handleSubmissionResponseFromForm($form);
    }

    public function handleSubmissionResponseFromForm(FormInterface $form): ?Response
    {
        if ($form->isSubmitted() && $form->isValid()) {
            $action = $this->getSuccessAction();
            if ($this->request && RequestHelper::isJsonRequest($this->request)) {
                if (!is_array($action)) {
                    $action = ['type' => self::ACTION_NO_ACTION];
                }

                if (($action['type'] ?? null) !== self::ACTION_REDIRECT) {
                    $errors = [
                        'form' => [],
                        'fields' => [],
                        'count' => 0,
                    ];
                    $payload = FormResponsePayload::fromForm($form)
                        ->setErrors($errors)
                        ->setAction($action);

                    return new JsonResponse($payload->toArray());
                }
            }

            if (is_array($action)
                && ($action['type'] ?? null) === self::ACTION_REDIRECT
                && !empty($action['url'])
            ) {
                $url = (string) $action['url'];
                if ($this->request && RequestHelper::isJsonRequest($this->request)) {
                    $errors = [
                        'form' => [],
                        'fields' => [],
                        'count' => 0,
                    ];
                    $payload = FormResponsePayload::fromForm($form)
                        ->setErrors($errors)
                        ->setAction([
                            'type' => self::ACTION_REDIRECT,
                            'url' => $url,
                        ]);

                    return new JsonResponse($payload->toArray());
                }

                return new RedirectResponse($url);
            }
        }

        return null;
    }

    protected function processSubmittedForm(FormInterface $form): void
    {
        $domainSet = false;

        if ($this->translator) {
            // TODO: Revisit translation domain switching when AJAX flow is fully active.
            $this->translator->setDomain(
                Translator::DOMAIN_TYPE_FORM,
                AbstractForm::transTypeDomain(static::getFormClass())
            );
            $domainSet = true;
        }

        try {
            if ($this->formIsSubmitted($form)) {
                $this->onSubmitted($form);

                $isValid = $this->formIsValid($form);

                if ($isValid) {
                    $this->onValid($form);
                } else {
                    $this->onInvalid($form);
                }
            }
        } finally {
            if ($domainSet) {
                $this->translator?->revertDomain(Translator::DOMAIN_TYPE_FORM);
            }
        }
    }
    public function createFormSubmitted(
        Request $request
    ): FormInterface {
        return $this->createForm(
            $this->handleSubmittedRequest($request)
        );
    }

    public function handleSubmittedRequest(Request $request)
    {
        return null;
    }

    public function onSubmitted(
        FormInterface $form
    ) {
        // To override by children.
    }

    public function formIsValid(FormInterface $form): bool
    {
        return $form->isValid();
    }

    public function onValid(
        FormInterface $form
    ) {
        // To override by children.
    }
    public function onInvalid(
        FormInterface $form
    ) {
        // To override by children.
    }

    public function setSuccessAction(array $action): void
    {
        $this->successAction = $action;
    }

    public function getSuccessAction(): ?array
    {
        return $this->successAction;
    }

    protected function addFormErrorFromApiKey(
        FormInterface $form,
        string $errorKey,
        string $prefix = 'error.'
    ): void {
        $key = $prefix . $errorKey . '.message';
        $translationKey = '@' . Translator::DOMAIN_TYPE_FORM
            . Translator::DOMAIN_SEPARATOR
            . $key;

        if ($this->translator) {
            $form->addError(new \Symfony\Component\Form\FormError(
                $this->translator->trans($translationKey)
            ));
        } else {
            $form->addError(new \Symfony\Component\Form\FormError($translationKey));
        }
    }

    public function getSuccessRedirectUrl(FormInterface $form): ?string
    {
        return null;
    }
    public function getRedirectUrl(): ?string
    {
        $action = $this->getSuccessAction();
        if (is_array($action)
            && ($action['type'] ?? null) === self::ACTION_REDIRECT
            && !empty($action['url'])
        ) {
            return (string) $action['url'];
        }

        return null;
    }

    public function translateKeys(array $keys): array
    {
        if (! $this->translator) {
            return [];
        }

        $translations = [];
        $uniqueKeys = array_values(array_unique(array_filter($keys)));

        foreach ($uniqueKeys as $key) {
            $lookupKey = $key;

            if (str_starts_with($lookupKey, Translator::DOMAIN_PREFIX)) {
                $lookupKey = substr($lookupKey, strlen(Translator::DOMAIN_PREFIX));
            }

            $translations[$key] = $this->translator->trans($lookupKey);
        }

        // TODO: Reevaluate whether translation should happen here or in response building.
        return $translations;
    }

    public function getRequiredRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function redirectToRoute(
        string $routeName,
        array $params = []
    ): void {
        $this->redirect(
            $this->urlGenerator->generate($routeName, $params)
        );
    }

    public function redirectToPreviousOrToRoute(
        string $routeName,
        array $parameters = []
    ): void {
        $target = null;

        if ($this->request) {
            $candidate = $this->request->get(self::REQUEST_REDIRECT_PARAM);
            $target = is_string($candidate) ? $candidate : null;
        }

        $session = $this->request?->getSession();

        if (! $target && $session) {
            $candidate = $session->get(self::SESSION_REDIRECT_TARGET)
                ?? $session->get(self::SESSION_SECURITY_TARGET);
            $target = is_string($candidate) ? $candidate : null;
        }

        if ($session) {
            $session->remove(self::SESSION_REDIRECT_TARGET);
            $session->remove(self::SESSION_SECURITY_TARGET);
        }

        if ($target && $this->isSafeRedirectTarget($target)) {
            $this->redirect($target);
            return;
        }

        $this->redirect($this->urlGenerator->generate($routeName, $parameters));
    }

    public function redirect(string $url): void
    {
        $this->setSuccessAction([
            'type' => self::ACTION_REDIRECT,
            'url' => $url,
        ]);
    }

    private function isSafeRedirectTarget(string $target): bool
    {
        return str_starts_with($target, '/') && ! str_starts_with($target, '//');
    }

    protected function getPostedRawData(string $key)
    {
        if (! $this->request) {
            return null;
        }

        $data = $this->request->get(
            ClassHelper::getTableizedName(static::getFormClass())
        );

        return $data[$key] ?? null;
    }
    protected static function guessFormClass(): ?string
    {
        $processorClass = static::class;
        $base = static::FORMS_PROCESSOR_CLASS_BASE_PATH;
        $suffix = static::CLASS_EXTENSION;

        if (! str_starts_with($processorClass, $base)) {
            return null;
        }

        $relative = substr($processorClass, strlen($base));

        if (! str_ends_with($relative, $suffix)) {
            return null;
        }

        $formRelative = substr($relative, 0, -strlen($suffix));

        return static::FORMS_CLASS_BASE_PATH . $formRelative;
    }
}
