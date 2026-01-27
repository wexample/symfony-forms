<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Psr\Container\ContainerInterface;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Wexample\Helpers\Helper\ClassHelper;

class FormProcessorPostHandler
{
    public function __construct(
        private readonly ContainerInterface $processors
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
        $form = $formProcessor->handleSubmission($request);

        return $formProcessor->render($form);
    }
}
