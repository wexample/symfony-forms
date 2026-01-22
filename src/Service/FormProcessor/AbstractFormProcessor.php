<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use RuntimeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyLoader\Service\AdaptiveFormResponseService;
use Wexample\SymfonyTranslations\Translation\Translator;

abstract class AbstractFormProcessor
{
    public const CLASS_EXTENSION = 'Processor';

    public const FORMS_CLASS_BASE_PATH = 'App\\Form\\';

    public const FORMS_PROCESSOR_CLASS_BASE_PATH = 'App\\Service\\FormProcessor\\';

    protected ?Request $request = null;
    protected ?Translator $translator = null;
    protected ?AdaptiveFormResponseService $adaptiveFormResponseService = null;

    public const VAR_FORM_DATA = 'formData';

    public function __construct(
        protected FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        protected UrlGeneratorInterface $urlGenerator,
        ?Translator $translator = null,
        ?AdaptiveFormResponseService $adaptiveFormResponseService = null
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->translator = $translator;
        $this->adaptiveFormResponseService = $adaptiveFormResponseService;
    }

    public function createForm(
        $data = null,
        array $options = []
    ): FormInterface {
        $formClass = static::getFormClass();

        if (!class_exists($formClass)) {
            throw new RuntimeException(
                sprintf(
                    'Unable to find form %s related to processor %s.',
                    $formClass,
                    static::class
                )
            );
        }

        if (property_exists($formClass, 'ajax')
            && $formClass::$ajax
            && !isset($options['action'])
        ) {
            $options['action'] = $this->createFormAction($data);
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

        if (!$formClass || !class_exists($formClass)) {
            throw new RuntimeException(sprintf(
                'Unable to resolve form class for %s. Override getFormClass().',
                static::class
            ));
        }

        return $formClass;
    }

    public function createFormAction($data): string
    {
        return $this->urlGenerator->generate(
            $this->getFormActionRoute(),
            $this->getFormActionArgs($data)
        );
    }

    public function getFormActionRoute(): string
    {
        return 'form_processor_submit';
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
    ): FormInterface
    {
        $form = $this->createFormSubmitted($request);

        $form->handleRequest($request);

        $domainSet = false;

        if ($this->translator) {
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

        return $form;
    }

    public function handleStaticFormOrRenderAdaptiveResponse(
        string $view,
        array $parameters = [],
        $formData = null,
        string $formParameterName = 'form'
    ): Response {
        if (!$this->request) {
            throw new RuntimeException('A request is required to handle form submission.');
        }

        if (!$this->adaptiveFormResponseService) {
            throw new RuntimeException('AdaptiveFormResponseService is required to render adaptive responses.');
        }

        $form = $this->createForm($formData);

        if ($form->handleRequest($this->request) && $this->formIsSubmitted($form)) {
            // Override first form by submitted one.
            $form = $this->handleSubmission($this->request);
        }

        $this->prepareDisplay($form);

        $this->adaptiveFormResponseService->setViewDefault($view);
        $this->adaptiveFormResponseService->addParameters(
            $this->buildViewParameters($form, $formParameterName)
            + $parameters
        );

        return $this->adaptiveFormResponseService->render();
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

    /**
     * Create only form when rendering is managed externally (ex: multiple form
     * in a page).
     */
    public function createFormView(
        $data = null,
        array $options = []
    ): FormView {
        $form = $this->createForm($data, $options);

        return $form->createView();
    }

    public function onInvalid(
        FormInterface $form
    ) {
        // To override by children.
    }

    public function getSuccessRedirectUrl(FormInterface $form): ?string
    {
        return null;
    }

    public function prepareDisplay(FormInterface $form): void
    {
        if (!$this->adaptiveFormResponseService
            || !$this->adaptiveFormResponseService->hasAction()
        ) {
            $this->onRender($form);
        }
    }

    public function onRender(FormInterface $form): void
    {
        // To override by children.
    }

    protected function buildViewParameters(
        FormInterface $form,
        string $formParameterName = 'form'
    ): array {
        return [
            self::VAR_FORM_DATA => $form->getData(),
            $formParameterName => $form->createView(),
        ];
    }

    public function render(FormInterface $form, string $formParameterName = 'form'): Response
    {
        if (!$this->adaptiveFormResponseService) {
            throw new RuntimeException('AdaptiveFormResponseService is required to render adaptive responses.');
        }

        $this->adaptiveFormResponseService->setForm($form, $formParameterName);

        return $this->adaptiveFormResponseService->render();
    }

    public function redirectToRoute(
        string $routeName,
        array $params = []
    ): void {
        $this->redirect(
            $this->urlGenerator->generate($routeName, $params)
        );
    }

    public function redirect(string $url): void
    {
        if (!$this->adaptiveFormResponseService) {
            throw new RuntimeException('AdaptiveFormResponseService is required to render redirects.');
        }

        $this->adaptiveFormResponseService->setRedirectUrl($url);
    }

    protected function getPostedRawData(string $key)
    {
        if (!$this->request) {
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

        if (!str_starts_with($processorClass, $base)) {
            return null;
        }

        $relative = substr($processorClass, strlen($base));

        if (!str_ends_with($relative, $suffix)) {
            return null;
        }

        $formRelative = substr($relative, 0, -strlen($suffix));

        return static::FORMS_CLASS_BASE_PATH . $formRelative;
    }
}
