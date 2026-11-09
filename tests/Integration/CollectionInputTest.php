<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Wexample\SymfonyForms\Form\Type\CollectionInputType;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\TextareaInputType;
use Wexample\SymfonyForms\Service\FormDefinitionBuilder;
use Wexample\SymfonyForms\Service\FormRenderingService;
use Wexample\SymfonyForms\Service\FormSchemaLoader;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * Several values of one kind: drawn an item each with what adds one, handed
 * to a vue as its entry and its items, read back in the order posted, an
 * item left empty dropped.
 */
class CollectionInputTest extends KernelTestCase
{
    use RendersFormsTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testItemsAreDrawnUnderTheirIndexWithThePrototypeOfTheNext(): void
    {
        $html = $this->renderForm($this->form(['property' => ['{"a": 1}', '{"b": 2}']]));

        $this->assertStringContainsString('name="node[property][0]"', $html);
        $this->assertStringContainsString('name="node[property][1]"', $html);
        $this->assertStringContainsString('collection-input--prototype" hidden disabled', $html);
        // The example the item added starts from, its index still to come.
        $this->assertStringContainsString('name="node[property][__name__]"', $html);
        $this->assertStringContainsString('{&quot;name&quot;: &quot;&quot;}', $html);
    }

    public function testTheItemsAreReadBackInTheOrderPostedTheEmptyDropped(): void
    {
        $form = $this->form(['property' => ['first', 'second']]);
        $form->submit(['property' => ['0' => 'second', '1' => 'first', '2' => '', '3' => 'third']]);

        $this->assertTrue($form->isValid());
        $this->assertSame(['second', 'first', 'third'], array_values($form->getData()['property']));
    }

    public function testAnEmptyListTakesEveryItemOff(): void
    {
        $form = $this->form(['property' => ['first']]);
        // Nothing posted for it, as the drawn field and the vue send an empty list.
        $form->submit([], true);

        $this->assertSame([], array_values($form->getData()['property'] ?? []));
    }

    public function testHandedAsItsEntryItsItemsAndItsExample(): void
    {
        $form = $this->factory()->createNamedBuilder('node', FormType::class, ['property' => ['{"a": 1}'], 'limits' => [3, 5]], ['action' => '/node'])
            ->add('property', CollectionInputType::class, [
                'label' => 'Property',
                'entry_type' => TextareaInputType::class,
                'entry_options' => ['attr' => ['rows' => 4]],
                'template' => '{"name": ""}',
                'required' => false,
            ])
            ->add('limits', CollectionInputType::class, ['label' => 'Limits', 'entry_type' => NumberInputType::class, 'allow_delete' => false, 'required' => false])
            ->getForm();

        $props = $this->builder()->build($form);
        $this->rendering()->validateAgainst($this->schemas()->load('form'), $props, 'node form');

        [$property, $limits] = $props['fields'];
        $this->assertSame('collection', $property['type']);
        $this->assertSame(['type' => 'textarea', 'name' => 'property', 'label' => 'Property', 'rows' => 4], array_intersect_key($property['entry'], array_flip(['type', 'name', 'label', 'rows'])));
        $this->assertSame(['{"a": 1}'], $property['items']);
        $this->assertSame('{"name": ""}', $property['template']);
        $this->assertArrayNotHasKey('value', $property);
        $this->assertSame(['number', ['3', '5'], true, false], [$limits['entry']['type'], $limits['items'], $limits['allowAdd'], $limits['allowDelete']]);
    }

    private function form(array $data)
    {
        return $this->factory()->createNamedBuilder('node', FormType::class, $data)
            ->add('property', CollectionInputType::class, [
                'label' => 'Property',
                'entry_type' => TextareaInputType::class,
                'template' => '{"name": ""}',
                'required' => false,
            ])
            ->getForm();
    }

    private function builder(): FormDefinitionBuilder
    {
        return self::getContainer()->get(FormDefinitionBuilder::class);
    }

    private function schemas(): FormSchemaLoader
    {
        return self::getContainer()->get(FormSchemaLoader::class);
    }

    private function rendering(): FormRenderingService
    {
        return self::getContainer()->get(FormRenderingService::class);
    }

    private function factory(): FormFactoryInterface
    {
        return self::getContainer()->get('form.factory');
    }
}
