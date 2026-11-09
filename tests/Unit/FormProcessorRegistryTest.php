<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Wexample\SymfonyForms\Exception\AmbiguousFormProcessorException;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Service\FormProcessor\FormProcessorRegistry;

/**
 * A lookup by name or by form class answers the form's one processor, nothing
 * for a form none handles, and refuses — for that form only — one several do.
 */
class FormProcessorRegistryTest extends TestCase
{
    private const string SINGLE = 'single_form';
    private const string SHARED = 'shared_form';

    /** @var array<string, AbstractFormProcessor> */
    private array $processors = [];

    protected function setUp(): void
    {
        foreach (['single', 'first', 'second'] as $id) {
            $this->processors[$id] = $this->createStub(AbstractFormProcessor::class);
        }
    }

    public function testTheOneProcessorOfAFormIsFound(): void
    {
        $registry = $this->registry();

        $this->assertSame($this->processors['single'], $registry->findByFormName(self::SINGLE));
        $this->assertSame($this->processors['single'], $registry->findByFormClass(self::SINGLE));
    }

    public function testAFormNoProcessorHandlesFindsNothing(): void
    {
        $this->assertNull($this->registry()->findByFormName('unknown_form'));
        $this->assertNull($this->registry()->findByFormClass('unknown_form'));
    }

    public function testAFormSeveralProcessorsHandleCannotBeLookedUp(): void
    {
        $this->expectException(AmbiguousFormProcessorException::class);
        $this->expectExceptionMessageMatches('/"shared_form" is handled by several processors \(first, second\)/');

        $this->registry()->findByFormName(self::SHARED);
    }

    /**
     * The other forms are not touched by the one that is ambiguous.
     */
    public function testAnAmbiguousFormLeavesTheOthersAlone(): void
    {
        $registry = $this->registry();

        try {
            $registry->findByFormClass(self::SHARED);
            $this->fail('An ambiguous form was looked up.');
        } catch (AmbiguousFormProcessorException) {
        }

        $this->assertSame($this->processors['single'], $registry->findByFormName(self::SINGLE));
    }

    private function registry(): FormProcessorRegistry
    {
        $map = [self::SINGLE => ['single'], self::SHARED => ['first', 'second']];

        return new FormProcessorRegistry(
            new ServiceLocator(array_map(
                static fn (AbstractFormProcessor $processor): \Closure => static fn (): AbstractFormProcessor => $processor,
                $this->processors
            )),
            $map,
            $map,
        );
    }
}
