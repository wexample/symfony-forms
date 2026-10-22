<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * The processor of a form editing a Doctrine entity — the one
 * `#[EntityFormProcessor]` hands it, or a new one —: valid, the entity is
 * saved. `beforeSave()` for what the form does not set, `afterSave()` for what
 * follows; by default the page carries on and says it is saved.
 */
abstract class AbstractEntityFormProcessor extends AbstractFormProcessor
{
    protected EntityManagerInterface $entityManager;

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function onValid(FormInterface $form): void
    {
        $entity = $form->getData();

        $this->beforeSave($entity, $form);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
        $this->afterSave($entity, $form);
    }

    protected function beforeSave(object $entity, FormInterface $form): void
    {
    }

    protected function afterSave(object $entity, FormInterface $form): void
    {
        $this->setSuccessAction(['type' => self::ACTION_DEFAULT]);
        $this->setNotification('@form::success.message');
    }
}
