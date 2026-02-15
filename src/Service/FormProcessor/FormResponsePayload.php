<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;

final class FormResponsePayload
{
    private array $payload = [
        'ok' => true,
        'form' => [
            'name' => '',
            'errors' => [
                'form' => [],
                'fields' => [],
                'count' => 0,
            ],
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
        $this->payload['ok'] = $count === 0;
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
        return $this->payload;
    }
}
