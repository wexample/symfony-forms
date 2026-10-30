<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Service\Attribute\Required;
use Wexample\SymfonyForms\Controller\FormController;

/**
 * The processor of a form editing a Doctrine entity — the one
 * `#[EntityFormProcessor]` hands it, or a new one —: valid, the entity is
 * saved. `beforeSave()` for what the form does not set, `afterSave()` for what
 * follows; by default the page carries on and says it is saved.
 *
 * Sent in the background (`ajax`), the form posts to the entity's own submit
 * address, its id in it, and the entity is found again there by the form's
 * `data_class`: the page that drew the form is not the one receiving it.
 */
abstract class AbstractEntityFormProcessor extends AbstractFormProcessor
{
    private const string ROUTE_PARAM_ID = 'id';

    protected EntityManagerInterface $entityManager;

    #[Required]
    public function setEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    public function getFormActionRoute(): string
    {
        return FormController::ROUTE_NAME_PREFIX.FormController::ROUTE_ENTITY_FORM_PROCESSOR_SUBMIT;
    }

    public function getFormActionArgs($data): array
    {
        $id = is_object($data) && method_exists($data, 'getId') ? $data->getId() : null;

        return parent::getFormActionArgs($data) + ($id !== null ? [self::ROUTE_PARAM_ID => (string) $id] : []);
    }

    public function handleSubmission(Request $request): FormInterface
    {
        $id = $request->attributes->get(self::ROUTE_PARAM_ID);

        // No id: the creation form, which edits a new entity.
        if ($id === null) {
            return parent::handleSubmission($request);
        }

        $entityClass = $this->formFactory->createBuilder(static::getFormClass())->getDataClass();
        $entity = $entityClass ? $this->entityManager->getRepository($entityClass)->find($id) : null;

        if (! $entity) {
            throw new NotFoundHttpException(($entityClass ?? static::getFormClass()).' not found: '.$id);
        }

        return $this->handleSubmissionWithData($request, $entity);
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
