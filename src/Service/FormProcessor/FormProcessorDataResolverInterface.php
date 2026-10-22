<?php

namespace Wexample\SymfonyForms\Service\FormProcessor;

use Symfony\Component\HttpFoundation\Request;

interface FormProcessorDataResolverInterface
{
    public function resolve(
        Request $request,
        array $options = []
    ): mixed;
}
