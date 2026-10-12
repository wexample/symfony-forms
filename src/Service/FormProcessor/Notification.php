<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

class Notification
{
    public const string TYPE_SUCCESS = 'success';
    public const string TYPE_ERROR = 'error';
    public const string TYPE_WARNING = 'warning';
    public const string TYPE_INFO = 'info';

    /**
     * @param array{label: string, href: string, icon?: string, target?: string}|null $action
     *   What to do next, drawn as a button in the toast: `target` opens `href`
     *   where a target button would — `panel`, `modal` —, beside the page.
     */
    public function __construct(
        private string $message,
        private string $type = self::TYPE_SUCCESS,
        private ?array $action = null
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getAction(): ?array
    {
        return $this->action;
    }

    public function setAction(?array $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type,
            'message' => $this->message,
            'action' => $this->action,
        ], fn ($value) => $value !== null);
    }
}
