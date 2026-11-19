<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Form\FieldExtension\FieldExtensions;
use Wexample\SymfonyForms\Form\Type\NumberInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyForms\Service\FormDefinitionBuilder;

/**
 * An extension holding a value of its own beside its field's — whether the
 * field enters a test matrix —: handed to the design system with the field,
 * sent back under `<field>__<extension>`, read out of the field's
 * transformers as the extension holds it, kept for whoever processes the form.
 */
class ValuedFieldExtensionTest extends KernelTestCase
{
    public function testItsValueIsHandedWithTheField(): void
    {
        self::bootKernel();
        $form = $this->form(true);

        [$temperature] = $this->builder()->build($form)['fields'];

        $this->assertSame(['value' => true, 'icon' => 'ph:bold/flask', 'label' => 'In the test matrix'], $temperature['extensions']['toggle']);
    }

    public function testItsValueIsReadBesideTheFieldsAndLeavesItAlone(): void
    {
        self::bootKernel();
        $form = $this->form(false);

        $form->submit(['temperature' => '0.7', 'temperature__toggle' => '1', 'model' => 'gpt']);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
        $this->assertTrue(FieldExtensions::submitted($form, 'temperature', 'toggle'));
        $this->assertEquals(0.7, $form->get('temperature')->getData());
    }

    public function testSwitchedOffItIsFalse(): void
    {
        self::bootKernel();
        $form = $this->form(true);

        $form->submit(['temperature' => '0.7', 'temperature__toggle' => '0', 'model' => 'gpt']);

        $this->assertTrue($form->isValid());
        $this->assertFalse(FieldExtensions::submitted($form, 'temperature', 'toggle'));
    }

    public function testNotSentItIsTheFieldsOwn(): void
    {
        self::bootKernel();
        $form = $this->form(true);

        $form->submit(['temperature' => '0.7', 'model' => 'gpt']);

        $this->assertTrue(FieldExtensions::submitted($form, 'temperature', 'toggle'));
    }

    private function form(bool $toggled): FormInterface
    {
        return $this->factory()->createBuilder(FormType::class, null, ['action' => '/configs'])
            ->add('temperature', NumberInputType::class, ['label' => 'Temperature', 'required' => false, 'extensions' => [
                'toggle' => ['value' => $toggled, 'icon' => 'ph:bold/flask', 'label' => 'In the test matrix'],
            ]])
            ->add('model', TextInputType::class, ['label' => 'Model', 'required' => false])
            ->getForm();
    }

    private function builder(): FormDefinitionBuilder
    {
        return self::getContainer()->get(FormDefinitionBuilder::class);
    }

    private function factory(): FormFactoryInterface
    {
        return self::getContainer()->get('form.factory');
    }
}
