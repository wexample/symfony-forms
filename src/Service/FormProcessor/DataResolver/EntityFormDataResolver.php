<?php

declare(strict_types=1);

namespace Wexample\SymfonyForms\Service\FormProcessor\DataResolver;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorDataResolverInterface;

/**
 * Loads the entity a form is about to edit.
 *
 * The subscriber builds the form on `kernel.request`, before the controller
 * runs, so the entity cannot come from a controller argument. Which entity is
 * the only thing that varies between two edit forms, and `repositoryClass` on
 * the Doctrine attribute already says where to look for it — so this resolves
 * for every entity rather than being written once per entity.
 */
class EntityFormDataResolver implements FormProcessorDataResolverInterface
{
    public const string OPTION_ENTITY_TYPE = 'entityType';
    private const string ROUTE_PARAM_ID = 'id';

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    public function resolve(
        Request $request,
        array $options = []
    ): mixed {
        $entityType = $options[self::OPTION_ENTITY_TYPE];
        $id = $request->attributes->get(self::ROUTE_PARAM_ID);

        // No id on the route is the creation form: the processor receives a
        // blank instance, and the same form class serves both.
        if ($id === null) {
            return null;
        }

        $entity = $this->entityManager
            ->getRepository($entityType)
            ->find($id);

        if (! $entity) {
            throw new NotFoundHttpException($entityType . ' not found: ' . $id);
        }

        return $entity;
    }
}
