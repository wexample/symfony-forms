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
 * An extension holding a value of its own beside its field's — the variable
 * a field is bound to —: handed to the design system with the field, sent
 * back under `<field>__<extension>`, read out of the field's transformers,
 * kept for whoever processes the form, refused on the field when wrong.
 */
class ValuedFieldExtensionTest extends KernelTestCase
{
    public function testItsValueIsHandedWithTheField(): void
    {
        self::bootKernel();
        $form = $this->form('temperature');

        [$temperature] = $this->builder()->build($form)['fields'];

        $this->assertSame('temperature', $temperature['extensions']['variable']['value']);
        $this->assertSame(['temperature', 'model'], $temperature['extensions']['variable']['suggestions']);
    }

    public function testItsValueIsReadBesideTheFieldsAndLeavesItAlone(): void
    {
        self::bootKernel();
        $form = $this->form('temperature');

        $form->submit(['temperature' => '0.7', 'temperature__variable' => 'heat', 'model' => 'gpt']);

        $this->assertTrue($form->isValid(), (string) $form->getErrors(true));
        $this->assertSame('heat', FieldExtensions::submitted($form, 'temperature', 'variable'));
        $this->assertEquals(0.7, $form->get('temperature')->getData());
    }

    public function testEmptiedItIsNone(): void
    {
        self::bootKernel();
        $form = $this->form('temperature');

        $form->submit(['temperature' => '0.7', 'temperature__variable' => '', 'model' => 'gpt']);

        $this->assertTrue($form->isValid());
        $this->assertNull(FieldExtensions::submitted($form, 'temperature', 'variable'));
    }

    public function testNotSentItIsTheFieldsOwn(): void
    {
        self::bootKernel();
        $form = $this->form('temperature');

        $form->submit(['temperature' => '0.7', 'model' => 'gpt']);

        $this->assertSame('temperature', FieldExtensions::submitted($form, 'temperature', 'variable'));
    }

    public function testAWrongValueIsRefusedOnItsField(): void
    {
        self::bootKernel();
        $form = $this->form(null);

        $form->submit(['temperature' => '0.7', 'temperature__variable' => '2 words', 'model' => 'gpt']);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('temperature')->getErrors());
        $this->assertCount(0, $form->get('model')->getErrors());
    }

    private function form(?string $variable): FormInterface
    {
        return $this->factory()->createBuilder(FormType::class, null, ['action' => '/configs'])
            ->add('temperature', NumberInputType::class, ['label' => 'Temperature', 'required' => false, 'extensions' => [
                'variable' => ['value' => $variable, 'suggestions' => ['temperature', 'model', 'temperature']],
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
