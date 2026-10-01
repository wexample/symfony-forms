<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Wexample\SymfonyForms\Form\Type\RadioInputType;
use Wexample\SymfonyForms\Form\Type\SelectInputType;

/**
 * A list of objects — entities, value objects — given as choices is read by
 * ChoiceType with `choice_value` / `choice_label`, not keyed by itself as a
 * list of scalars is.
 */
class ObjectChoicesTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testAListOfObjectsIsAcceptedAndSubmitted(): void
    {
        $first = new ObjectChoicesTestItem('a', 'First');
        $second = new ObjectChoicesTestItem('b', 'Second');

        foreach ([SelectInputType::class, RadioInputType::class] as $type) {
            $form = self::getContainer()->get('form.factory')
                ->createBuilder(FormType::class)
                ->add('item', $type, [
                    'choices' => [$first, $second],
                    'choice_value' => fn (?ObjectChoicesTestItem $item) => $item?->id,
                    'choice_label' => fn (ObjectChoicesTestItem $item) => $item->label,
                    'auto_translate_choices' => false,
                ])
                ->getForm();

            $form->submit(['item' => 'b']);

            $this->assertSame($second, $form->get('item')->getData(), $type);
        }
    }

    public function testAListOfScalarsIsStillKeyedByItself(): void
    {
        $form = self::getContainer()->get('form.factory')
            ->createBuilder(FormType::class)
            ->add('size', SelectInputType::class, ['choices' => ['s', 'm']])
            ->getForm();

        $form->submit(['size' => 'm']);

        $this->assertSame('m', $form->get('size')->getData());
    }
}

final class ObjectChoicesTestItem
{
    public function __construct(public readonly string $id, public readonly string $label)
    {
    }
}
