<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Wexample\SymfonyForms\Service\FormProcessor\FormResponsePayloadBuilder;
use Wexample\SymfonyForms\Service\FormRenderingService;
use Wexample\SymfonyForms\Service\FormSchemaLoader;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\ContactForm;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Contact;
use Wexample\SymfonyForms\Tests\Fixtures\App\Service\FormProcessor\ContactFormProcessor;

/**
 * What a form answers in json is what the json-schema norm says it answers
 * (`form_submit_response.json`): refused, its errors under each field's full
 * name; taken, that it is.
 */
class FormResponsePayloadTest extends KernelTestCase
{
    public function testARefusedFormAnswersAsTheNormSays(): void
    {
        self::bootKernel();
        $form = $this->factory()->create(ContactForm::class, new Contact(), ['action' => '/contacts/save']);
        $form->submit(['name' => '']);
        $form->get('name')->addError(new FormError('field.name.error.blank'));
        $form->addError(new FormError('form.error.stale'));

        $payload = $this->builder()->build(self::getContainer()->get(ContactFormProcessor::class), $form);

        $this->rendering()->validateAgainst($this->schemas()->load('form_submit_response'), $payload, 'refused answer');
        $this->assertFalse($payload['ok']);
        // The fields' errors go as an object, keyed by the fields' full names.
        $this->assertSame(['field.name.error.blank'], ((array) $payload['form']['errors']['fields'])['contact_form[name]']);
        $this->assertContains('form.error.stale', $payload['form']['errors']['form']);
    }

    public function testATakenFormAnswersAsTheNormSays(): void
    {
        self::bootKernel();
        $form = $this->factory()->create(ContactForm::class, new Contact(), ['action' => '/contacts/save']);
        $form->submit(['name' => 'Ada', 'phone' => '', 'newsletter' => false]);

        $payload = $this->builder()->build(self::getContainer()->get(ContactFormProcessor::class), $form);

        $this->rendering()->validateAgainst($this->schemas()->load('form_submit_response'), $payload, 'taken answer');
        $this->assertTrue($payload['ok']);
        $this->assertSame(0, $payload['form']['errors']['count']);
    }

    private function builder(): FormResponsePayloadBuilder
    {
        return self::getContainer()->get(FormResponsePayloadBuilder::class);
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
