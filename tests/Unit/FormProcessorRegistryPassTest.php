<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wexample\Helpers\Helper\ClassHelper;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Wexample\SymfonyForms\DependencyInjection\Compiler\FormProcessorRegistryPass;
use Wexample\SymfonyForms\DependencyInjection\WexampleSymfonyFormsExtension;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorRegistry;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\ContactForm;
use Wexample\SymfonyForms\Tests\Fixtures\App\Service\FormProcessor\ContactFormProcessor;
use Wexample\SymfonyForms\Tests\Fixtures\Processor\OrphanFormProcessor;
use Wexample\SymfonyForms\Tests\Fixtures\Processor\SecondContactFormProcessor;

/**
 * The processor of each form, asked once when the container is compiled: a
 * processor unable to answer fails the build, not every form submission.
 */
class FormProcessorRegistryPassTest extends TestCase
{
    public function testEachFormIsMappedToItsProcessorByClassAndByName(): void
    {
        $registry = $this->compile([ContactFormProcessor::class]);

        $this->assertSame(
            [ContactForm::class => ContactFormProcessor::class],
            $registry->getArgument('$processorsByFormClass')
        );
        // The name the processor writes in its form's submit address.
        $this->assertSame(
            [ClassHelper::longTableized(ContactForm::class) => ContactFormProcessor::class],
            $registry->getArgument('$processorsByFormName')
        );
    }

    /**
     * What took every form of an application down at runtime.
     */
    public function testAProcessorWithoutAFormFailsTheBuild(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/"' . preg_quote(OrphanFormProcessor::class, '/') . '" cannot name its form class/');

        $this->compile([ContactFormProcessor::class, OrphanFormProcessor::class]);
    }

    public function testAFormClaimedByTwoProcessorsFailsTheBuild(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessageMatches('/claimed by two processors/');

        $this->compile([ContactFormProcessor::class, SecondContactFormProcessor::class]);
    }

    /**
     * A base class an application's resource scan picked up has no form.
     */
    public function testAnAbstractProcessorIsLeftOut(): void
    {
        $registry = $this->compile([AbstractFormProcessor::class, ContactFormProcessor::class]);

        $this->assertCount(1, $registry->getArgument('$processorsByFormClass'));
    }

    /**
     * @param list<class-string> $processors
     */
    private function compile(array $processors): Definition
    {
        $container = new ContainerBuilder();
        $container->setDefinition(FormProcessorRegistry::class, new Definition(FormProcessorRegistry::class));

        foreach ($processors as $processor) {
            $container
                ->setDefinition($processor, new Definition($processor))
                ->addTag(WexampleSymfonyFormsExtension::TAG_FORM_PROCESSOR);
        }

        (new FormProcessorRegistryPass())->process($container);

        return $container->getDefinition(FormProcessorRegistry::class);
    }
}
