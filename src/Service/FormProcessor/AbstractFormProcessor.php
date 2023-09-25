<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use App\Wex\BaseBundle\Form\AbstractForm;
use App\Wex\BaseBundle\Form\FormError\FormErrorTranslated;
use App\Wex\BaseBundle\Service\AdaptiveEventsBag;
use App\Wex\BaseBundle\Service\AdaptiveResponse;
use App\Wex\BaseBundle\Translation\Translator;
use App\Wex\BaseBundle\Twig\TranslationExtension;
use Exception;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\SymfonyHelpers\Helper\ClassHelper;
use Wexample\SymfonyHelpers\Helper\VariableHelper;

abstract class AbstractFormProcessor
{
    public const CLASS_EXTENSION = 'Processor';

    public const FORMS_CLASS_BASE_PATH = 'App\\Form\\';

    public const FORMS_PROCESSOR_CLASS_BASE_PATH = 'App\\Service\\FormProcessor\\';

    protected ?Request $request = null;

    protected Translator $translator;

    public const VAR_FORM_DATA = 'formData';

    public function __construct(
        protected FormFactoryInterface $formFactory,
        protected UrlGeneratorInterface $urlGenerator,
        RequestStack $requestStack,
        protected AdaptiveResponse $adaptiveResponse,
        protected AdaptiveEventsBag $adaptiveEventsBag,
        protected TranslationExtension $transExt
    ) {
        $this->request = $requestStack->getCurrentRequest();
    }

    public function handleStaticFormOrRenderAdaptiveResponse(
        string $view,
        array $parameters = [],
        $formData = null
    ): Response {
        $form = $this->createForm($formData);

        if ($form->handleRequest($this->request)
            && $this->formIsSubmitted($form)) {
            // Override first form by submitted one.
            $form = $this->handleSubmission($this->request);
        }

        $this->prepareDisplay($form);

        $this->adaptiveResponse->setViewDefault(
            $view,
            $this->buildViewParameters($form)
            + $parameters
        );

        return $this->render($form);
    }

    public function createForm(
        $data,
        array $options = []
    ): FormInterface {
        $formClass = static::getFormClass();

        if (!class_exists($formClass)) {
            throw new Exception('Unable to find form '.$formClass.' related to processor '.$this::class);
        }

        if ($formClass::$ajax && !isset($options['action'])) {
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
    #[Pure]
    public static function getFormClass(): string
    {
        return ClassHelper::getCousin(
            static::class,
            static::FORMS_PROCESSOR_CLASS_BASE_PATH,
            AbstractFormProcessor::CLASS_EXTENSION,
            static::FORMS_CLASS_BASE_PATH
        );
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

    #[ArrayShape(['name' => 'string'])]
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

    public function handleSubmission(Request $request): FormInterface
    {
        $form = $this->createFormSubmitted($request);

        $form->handleRequest($request);

        $this->transExt->translator->setDomain(
            Translator::DOMAIN_TYPE_FORM,
            AbstractForm::transTypeDomain(static::getFormClass())
        );

        if ($this->formIsSubmitted($form)) {
            $this->onSubmitted($form);

            $isValid = $this->formIsValid($form);
            $hasError = (bool) $form->getErrors(true)->count();

            if (!$hasError) {
                if ($isValid) {
                    $this->onValid($form);
                } else {
                    $this->addFormMessageError('not_valid', [], 'forms');
                }
            }
        } else {
            $this->addFormMessageError('not_submitted', [], 'forms');
        }

        $this->transExt->transJs(
            '@'.Translator::DOMAIN_TYPE_FORM.Translator::DOMAIN_SEPARATOR.'*'
        );
        $this->transExt->translator->revertDomain(Translator::DOMAIN_TYPE_FORM);

        return $form;
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

    public function addFormMessageError(
        string $name,
        array $args = [],
        $domain = null
    ) {
        $this->adaptiveEventsBag->addPageMessageError(
            $name,
            $args,
            $domain
            ?? $this->transExt->translator->resolveDomain('@'.Translator::DOMAIN_TYPE_FORM)
        );
    }

    public function prepareDisplay($form): void
    {
        if (!$this->adaptiveResponse->hasAction()) {
            $this->onRender($form);
        }
    }

    public function onRender(FormInterface $form): void
    {
        // To override...
    }

    #[ArrayShape([
        self::VAR_FORM_DATA => VariableHelper::VARIABLE_TYPE_MIXED,
        VariableHelper::FORM => FormView::class,
    ])]
    protected function buildViewParameters(FormInterface $form): array
    {
        return [
            self::VAR_FORM_DATA => $form->getData(),
            VariableHelper::FORM => $form->createView(),
        ];
    }

    public function render(FormInterface $form): Response
    {
        return $this->adaptiveResponse->setForm($form)->render();
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

        $this->prepareDisplay($form);

        return $form->createView();
    }

    public function redirectToRoute(
        string $routeName,
        array $params = []
    ) {
        $this->redirect(
            $this->urlGenerator->generate($routeName, $params)
        );
    }

    public function redirect(string $url)
    {
        $this->adaptiveResponse->setRedirect(
            new RedirectResponse($url)
        );
    }

    public function addFormMessageSuccess(
        string $name = 'submit',
        array $args = [],
        $domain = null
    ) {
        $this->adaptiveEventsBag->addPageMessageSuccess(
            $name,
            $args,
            $domain
            ?? $this->transExt->translator->resolveDomain('@'.Translator::DOMAIN_TYPE_FORM)
        );
    }

    protected function getPostedRawData(string $key)
    {
        $data = $this->request->get(
            ClassHelper::getTableizedName(static::getFormClass())
        );

        return $data[$key] ?? null;
    }

    public function addFieldError(
        FormInterface $form,
        string $fieldName,
        string $error
    ): void {
        $form
            ->get($fieldName)
            ->addError(
                new FormErrorTranslated('field.'.$fieldName.'.error.'.$error, $form)
            );
    }

    protected function setFieldError(
        FormInterface $form,
        string $fieldName,
        string $key
    ) {
        $form->get('plainPassword')->get($fieldName)->addError(
            new FormError(
                $this->transField(
                    'field.'.$fieldName,
                    'error.'.$key
                )
            )
        );
    }

    protected function transField(
        string $fieldName,
        string $key
    ) {
        return AbstractForm::transForm(
            $fieldName.'.'.$key,
            self::getFormClass()
        );
    }
}
