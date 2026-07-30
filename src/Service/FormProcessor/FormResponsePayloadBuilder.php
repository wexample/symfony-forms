<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Form\AbstractForm;
use Wexample\SymfonyForms\Helper\FormHelper;
use Wexample\SymfonyTranslations\Translation\Translator;

class FormResponsePayloadBuilder
{
    public function __construct(
        private readonly Translator $translator
    ) {
    }

    public function build(
        AbstractFormProcessor $formProcessor,
        FormInterface $form
    ): array {
        [$errors, $keys] = $this->collectErrors($form);

        $payload = FormResponsePayload::fromForm($form)
            ->setErrors($errors)
            ->setNotification($formProcessor->getNotification())
            ->setAction(
                $formProcessor->getSuccessAction()
                    ?: ['type' => AbstractFormProcessor::ACTION_DEFAULT]
            );

        $translations = $this->translateKeys(
            $keys,
            $formProcessor,
            $form
        );

        if ($translations) {
            $payload->setTranslations($translations);
        }

        return $payload->toArray();
    }

    /**
     * @return array{0: array, 1: string[]} The payload errors and their translation keys.
     */
    private function collectErrors(FormInterface $form): array
    {
        $errors = [
            'form' => [],
            'fields' => [],
            'count' => 0,
        ];
        $keys = [];

        foreach ($form->getErrors(true, true) as $error) {
            $origin = $error->getOrigin();
            $message = $error->getMessage();
            $keys[] = $message;
            ++$errors['count'];

            if (! $origin || $origin === $form) {
                $errors['form'][] = $message;

                continue;
            }

            $errors['fields'][FormHelper::buildFullFieldName($origin)][] = $message;
        }

        return [$errors, $keys];
    }

    private function translateKeys(
        array $keys,
        AbstractFormProcessor $formProcessor,
        FormInterface $form
    ): array {
        if (empty($keys)) {
            return [];
        }

        $translations = [];
        $this->translator->setDomain(
            Translator::DOMAIN_TYPE_FORM,
            $form->getConfig()->getOption('translation_domain')
                ?? AbstractForm::transTypeDomain($formProcessor::getFormClass())
        );

        foreach ($keys as $key) {
            $translations[$key] = $this->translator->trans($key);
        }

        $this->translator->revertDomain(Translator::DOMAIN_TYPE_FORM);

        return $translations;
    }
}
