<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use RuntimeException;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Wexample\Helpers\Helper\ClassHelper;

abstract class AbstractFormProcessor
{
    public const CLASS_EXTENSION = 'Processor';

    public const FORMS_CLASS_BASE_PATH = 'App\\Form\\';

    public const FORMS_PROCESSOR_CLASS_BASE_PATH = 'App\\Service\\FormProcessor\\';

    protected ?Request $request = null;

    public function __construct(
        protected FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        protected ?UrlGeneratorInterface $urlGenerator = null
    ) {
        $this->request = $requestStack->getCurrentRequest();
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
        if (!$this->urlGenerator) {
            throw new RuntimeException('UrlGeneratorInterface is required to build form actions.');
        }

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

        if ($this->formIsSubmitted($form)) {
            $this->onSubmitted($form);

            $isValid = $this->formIsValid($form);

            if ($isValid) {
                $this->onValid($form);
            } else {
                $this->onInvalid($form);
            }
        }

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
