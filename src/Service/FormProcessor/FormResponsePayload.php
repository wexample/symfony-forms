<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyLoader\Response\AdaptiveResponse;

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
        if (! empty($translations)) {
            $this->payload['translations'] = $translations;
        }

        return $this;
    }

    public function setNotification(?Notification $notification): self
    {
        if ($notification) {
            $this->payload['notification'] = $notification->toArray();
        }

        return $this;
    }

    public function setAction(array $action): self
    {
        $this->payload['action'] = $action;

        return $this;
    }

    public function toArray(): array
    {
        $payload = $this->payload;
        // The fields' errors are keyed by name: an object in json even with
        // none, as the norm says (form_submit_response.schema.json) — a php array
        // with no key would go as a list.
        $payload['form']['errors']['fields'] = (object) ($payload['form']['errors']['fields'] ?? []);

        return parent::toArray() + $payload;
    }
}
