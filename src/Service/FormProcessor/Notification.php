<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

class Notification
{
    public const string TYPE_SUCCESS = 'success';
    public const string TYPE_ERROR = 'error';
    public const string TYPE_WARNING = 'warning';
    public const string TYPE_INFO = 'info';

    public function __construct(
        private string $message,
        private string $type = self::TYPE_SUCCESS
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

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'message' => $this->message,
        ];
    }
}
