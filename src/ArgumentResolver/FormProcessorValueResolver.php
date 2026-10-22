<?php

namespace Wexample\SymfonyForms\ArgumentResolver;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Wexample\SymfonyForms\Attribute\FormProcessor;
use Wexample\SymfonyForms\EventSubscriber\FormProcessorRequestSubscriber;
use Wexample\SymfonyHelpers\Helper\RouteHelper;

class FormProcessorValueResolver implements ValueResolverInterface
{
    private function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() !== FormInterface::class) {
            return false;
        }

        $reflection = RouteHelper::resolveControllerMethodReflection($request);
        if (! $reflection) {
            return false;
        }
        $attributes = $reflection->getAttributes(
            FormProcessor::class,
            \ReflectionAttribute::IS_INSTANCEOF
        );

        if (empty($attributes)) {
            return false;
        }

        foreach ($attributes as $attribute) {
            /** @var FormProcessor $config */
            $config = $attribute->newInstance();
            if ($config->formArgumentName === $argument->getName()) {
                return true;
            }
        }

        return false;
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if (! $this->supports($request, $argument)) {
            return [];
        }

        $forms = $request->attributes->get(
            FormProcessorRequestSubscriber::REQUEST_FORMS_ATTRIBUTE,
            []
        );

        if (isset($forms[$argument->getName()])) {
            yield $forms[$argument->getName()];
        }
    }
}
