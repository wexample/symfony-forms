<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyLoader\Response\AdaptiveResponse;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;

final class FormResponsePayload extends AdaptiveResponse
{
    protected string $responseType = 'form';
    private array $payload = [
        'form' => [
            'name' => '',
            'errors' => [
                'form' => [],
                'fields' => [],
                'count' => 0,
            ],
        ],
        'action' => [
            'type' => AbstractFormProcessor::ACTION_DEFAULT,
        ],
    ];

    public static function fromForm(FormInterface $form): self
    {
        $self = new self();
        $self->payload['form']['name'] = $form->getName();

        return $self;
    }

    public function setErrors(array $errors): self
    {
        $count = (int) ($errors['count'] ?? 0);
        $this->ok = $count === 0;
        $this->payload['form']['errors'] = $errors;

        return $this;
    }

    public function setTranslations(array $translations): self
    {
        if (!empty($translations)) {
            $this->payload['translations'] = $translations;
        }

        return $this;
    }

    public function setAction(array $action): self
    {
        $this->payload['action'] = $action;

        return $this;
    }

    public function setRender(array $render): self
    {
        $this->payload['render'] = $render;

        return $this;
    }

    public function toArray(): array
    {
        return parent::toArray() + $this->payload;
    }
}
