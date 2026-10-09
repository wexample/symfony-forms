<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Validator\Constraints\Length;
use Wexample\SymfonyForms\Form\Type\CheckboxInputType;
use Wexample\SymfonyForms\Form\Type\EmailInputType;
use Wexample\SymfonyForms\Form\Type\TelInputType;
use Wexample\SymfonyForms\Form\Type\TextInputType;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Contact;
use Wexample\SymfonyForms\Tests\Traits\RendersFormsTrait;

/**
 * The phone and checkbox inputs, and the length a text field is held to.
 */
class FieldTypesTest extends KernelTestCase
{
    use RendersFormsTrait;

    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testATextFieldTakesItsMaxLengthFromTheMappedProperty(): void
    {
        $html = $this->renderForm($this->contactForm());

        $this->assertStringContainsString('maxlength="40"', $this->tagWithId($html, 'contact_name'));
        $this->assertStringNotContainsString('maxlength', $this->tagWithId($html, 'contact_phone'));
    }

    public function testTheShortestLengthWinsAndAnExplicitOneStays(): void
    {
        $factory = self::getContainer()->get('form.factory');

        $form = $factory->createNamedBuilder('contact', FormType::class, new Contact(), ['data_class' => Contact::class])
            ->add('name', TextInputType::class, ['constraints' => [new Length(max: 25)]])
            ->getForm();
        $this->assertStringContainsString('maxlength="25"', $this->tagWithId($this->renderForm($form), 'contact_name'));

        $form = $factory->createNamedBuilder('contact', FormType::class, null, [])
            ->add('email', EmailInputType::class, ['constraints' => [new Length(max: 80)], 'attr' => ['maxlength' => 60]])
            ->getForm();
        $this->assertStringContainsString('maxlength="60"', $this->tagWithId($this->renderForm($form), 'contact_email'));
    }

    public function testAPhoneIsATelInput(): void
    {
        $this->assertStringContainsString('type="tel"', $this->tagWithId($this->renderForm($this->contactForm()), 'contact_phone'));
    }

    public function testACheckboxIsDrawnAndSubmittedAsOne(): void
    {
        $form = $this->contactForm();
        $html = $this->renderForm($form);

        $tag = $this->tagWithId($html, 'contact_newsletter');
        $this->assertStringContainsString('type="checkbox"', $tag);
        $this->assertStringContainsString('form--checkbox', $tag);
        $this->assertStringNotContainsString('switch', $html);

        $form->submit(['name' => 'Ada', 'phone' => '+32 470 00 00 00', 'newsletter' => '1']);
        $this->assertTrue($form->getData()->newsletter);
        $this->assertSame('+32 470 00 00 00', $form->getData()->phone);
    }

    private function contactForm()
    {
        return self::getContainer()->get('form.factory')
            ->createNamedBuilder('contact', FormType::class, new Contact(), ['data_class' => Contact::class])
            ->add('name', TextInputType::class, ['label' => false])
            ->add('phone', TelInputType::class, ['label' => false, 'required' => false])
            ->add('newsletter', CheckboxInputType::class, ['label' => false, 'required' => false])
            ->getForm();
    }
}
