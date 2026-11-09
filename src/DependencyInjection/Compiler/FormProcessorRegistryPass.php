<?php

namespace Wexample\SymfonyForms\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Wexample\Helpers\Helper\ClassHelper;
use Wexample\SymfonyForms\DependencyInjection\WexampleSymfonyFormsExtension;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorRegistry;

/**
 * Asks every tagged processor its form class once, here, and hands the
 * answers to the registry as maps.
 *
 * A processor that cannot name its form class, or a form claimed by two
 * processors, fails the container build — `cache:clear`, the deploy — instead
 * of every form submission of the running application.
 */
class FormProcessorRegistryPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition(FormProcessorRegistry::class)) {
            return;
        }

        $byFormClass = [];
        $byFormName = [];

        foreach (array_keys($container->findTaggedServiceIds(WexampleSymfonyFormsExtension::TAG_FORM_PROCESSOR)) as $serviceId) {
            $definition = $container->findDefinition($serviceId);
            $class = $container->getParameterBag()->resolveValue($definition->getClass() ?? $serviceId);

            if ($definition->isAbstract() || ! is_a($class, AbstractFormProcessor::class, true)
                || (new \ReflectionClass($class))->isAbstract()) {
                continue;
            }

            try {
                /** @var class-string<AbstractFormProcessor> $class */
                $formClass = $class::getFormClass();
            } catch (\Throwable $exception) {
                throw new LogicException(sprintf(
                    'Form processor "%s" cannot name its form class: %s',
                    $serviceId,
                    $exception->getMessage()
                ), 0, $exception);
            }

            if (isset($byFormClass[$formClass])) {
                throw new LogicException(sprintf(
                    'Form "%s" is claimed by two processors, "%s" and "%s"; a form has one.',
                    $formClass,
                    $byFormClass[$formClass],
                    $serviceId
                ));
            }

            $byFormClass[$formClass] = $serviceId;
            $byFormName[ClassHelper::longTableized($formClass)] = $serviceId;
        }

        $container->getDefinition(FormProcessorRegistry::class)
            ->setArgument('$processorsByFormClass', $byFormClass)
            ->setArgument('$processorsByFormName', $byFormName);
    }
}
