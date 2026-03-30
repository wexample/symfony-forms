<?php

namespace Wexample\SymfonyForms\Service\FormProcessor\DataResolver;

use Symfony\Component\HttpFoundation\Request;
use Wexample\SymfonyForms\Form\Data\EntityEditFormData;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorDataResolverInterface;

class EntityEditFormDataResolver implements FormProcessorDataResolverInterface
{
    private const string OPTION_ENTITY_TYPE = 'entityType';
    private const string ROUTE_PARAM_SECURE_ID = 'secureId';

    public function resolve(
        Request $request,
        array $options = []
    ): mixed {
        return new EntityEditFormData(
            $options[self::OPTION_ENTITY_TYPE],
            $request->attributes->get(self::ROUTE_PARAM_SECURE_ID)
        );
    }
}
