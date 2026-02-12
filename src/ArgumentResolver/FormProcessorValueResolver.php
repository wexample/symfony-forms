<?php

namespace Wexample\SymfonyForms\ArgumentResolver;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Wexample\SymfonyForms\Attribute\FormProcessor;
use Wexample\SymfonyForms\EventSubscriber\FormProcessorRequestSubscriber;

class FormProcessorValueResolver implements ValueResolverInterface
{
    private function supports(Request $request, ArgumentMetadata $argument): bool
    {
        if ($argument->getType() !== FormInterface::class) {
            return false;
        }

        $controller = $request->attributes->get('_controller');
        if (!is_string($controller) || !str_contains($controller, '::')) {
            return false;
        }

        [$class, $method] = explode('::', $controller, 2);
        if (!class_exists($class) || !method_exists($class, $method)) {
            return false;
        }

        $reflection = new \ReflectionMethod($class, $method);
        $attributes = $reflection->getAttributes(FormProcessor::class);

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
        if (!$this->supports($request, $argument)) {
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
