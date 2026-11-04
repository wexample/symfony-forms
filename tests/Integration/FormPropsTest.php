<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Wexample\SymfonyForms\Service\FormPropsBuilder;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\ContactForm;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Contact;

/**
 * A form handed to a vue: sent as the form drawn would be, each field under
 * its name, with its value and its limits.
 */
class FormPropsTest extends KernelTestCase
{
    public function testAFieldIsHandedWithItsNameValueAndLimits(): void
    {
        self::bootKernel();
        $contact = new Contact();
        $contact->name = 'Ada';

        $props = $this->builder()->build($this->factory()->create(ContactForm::class, $contact, ['action' => '/contacts/save']));

        $this->assertSame('contact_form', $props['name']);
        $this->assertSame('/contacts/save', $props['action']);
        $this->assertSame('POST', $props['method']);
        $name = $props['fields']['name'];
        $this->assertSame('contact_form[name]', $name['name']);
        $this->assertSame('Ada', $name['value']);
        $this->assertTrue($name['required']);
        $this->assertSame(40, $name['maxlength']);
        $this->assertSame('text', $name['type']);
        $this->assertSame('tel', $props['fields']['phone']['type']);
        $this->assertFalse($props['fields']['phone']['required']);
        $this->assertNull($props['fields']['phone']['maxlength']);
    }

    public function testTheEntityIdInTheActionGivesWayToAPlaceholder(): void
    {
        self::bootKernel();
        $contact = new class () extends Contact {
            public function getId(): string
            {
                return '42';
            }
        };

        $form = $this->factory()->create(ContactForm::class, $contact, ['action' => '/_forms/submit/contact_form/entity/42']);

        $this->assertSame(
            '/_forms/submit/contact_form/entity/__id__',
            $this->builder()->build($form, ['id_placeholder' => '__id__'])['action']
        );
        $this->assertSame('/_forms/submit/contact_form/entity/42', $this->builder()->build($form)['action']);
    }

    public function testALayoutMakesTheFormByItsClass(): void
    {
        self::bootKernel();
        $contact = new Contact();
        $contact->name = 'Ada';

        $props = self::getContainer()->get('twig')
            ->createTemplate("{{ form_props_for('Wexample\\\\SymfonyForms\\\\Tests\\\\Fixtures\\\\App\\\\Form\\\\ContactForm', contact)|json_encode|raw }}")
            ->render(['contact' => $contact]);
        $props = json_decode($props, true);

        $this->assertSame('contact_form', $props['name']);
        $this->assertSame('Ada', $props['fields']['name']['value']);
    }

    private function builder(): FormPropsBuilder
    {
        return self::getContainer()->get(FormPropsBuilder::class);
    }

    private function factory(): FormFactoryInterface
    {
        return self::getContainer()->get('form.factory');
    }
}
