<?php

namespace Wexample\SymfonyForms\Form\Data;

final class EntityEditFormData
{
    public function __construct(
        private string $entityType,
        private string $id,
        private ?array $formData = null,
        private ?object $entity = null
    ) {
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFormData(): ?array
    {
        return $this->formData;
    }

    public function setFormData(?array $formData): self
    {
        $this->formData = $formData;

        return $this;
    }

    public function getEntity(): ?object
    {
        return $this->entity;
    }

    public function setEntity(?object $entity): self
    {
        $this->entity = $entity;

        return $this;
    }
}
