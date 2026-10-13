<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyCheck\Checker\AbstractRequiredFieldsChecker;
use Wexample\SymfonyCheck\Class\Finding;
use Wexample\SymfonyCheck\Class\RouteRemedy;
use Wexample\SymfonyCheck\Helper\GateHelper;
use Wexample\SymfonyForms\Helper\FormGateHelper;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Contact;
use Wexample\SymfonyForms\Tests\Fixtures\App\Service\FormProcessor\ContactFormProcessor;

/**
 * A form a gate sent the user to: it points at what is missing, and once
 * saved goes back to the action that was stopped.
 */
class GateFormTest extends KernelTestCase
{
    private const string DESTINATION = '/invoices/new';

    public function testMissingFieldsArePointedAt(): void
    {
        $form = $this->processor($this->gateRequest('GET'))->createForm(new Contact());

        $this->assertSame(
            FormGateHelper::TRANSLATION_DOMAIN . '::field_required',
            $form->get('name')->getErrors()[0]->getMessage()
        );
        $this->assertCount(0, $form->get('phone')->getErrors());

        $messages = array_map(fn ($error) => $error->getMessage(), iterator_to_array($form->getErrors()));
        // A path the form does not hold is shown above it, in the words of its checker.
        $this->assertSame([
            FormGateHelper::TRANSLATION_DOMAIN . '::complete_to_continue',
            'billing::billing.country_unsupported',
        ], $messages);
    }

    public function testTheTextsAreTheFormOwn(): void
    {
        self::bootKernel();
        $translator = static::getContainer()->get('translator');

        foreach (['complete_to_continue', 'field_required'] as $key) {
            $this->assertNotSame(
                FormGateHelper::TRANSLATION_DOMAIN . '::' . $key,
                $translator->trans(FormGateHelper::TRANSLATION_DOMAIN . '::' . $key)
            );
        }
    }

    public function testAFormNotSentByAGateIsLeftAlone(): void
    {
        $form = $this->processor(Request::create('/contact'))->createForm(new Contact());

        $this->assertCount(0, $form->getErrors(true));
    }

    public function testOnceSavedItGoesBackToTheStoppedAction(): void
    {
        $processor = $this->processor($request = $this->gateRequest('POST'));
        $form = $processor->createForm(new Contact());
        $request->request->set($form->getName(), ['name' => 'Ada']);

        $form = $processor->handleSubmissionWithData($request, new Contact());

        $this->assertTrue($form->isValid());
        $this->assertSame(
            ['type' => AbstractFormProcessor::ACTION_REDIRECT, 'url' => self::DESTINATION],
            $processor->getSuccessAction()
        );
    }

    private function gateRequest(string $method): Request
    {
        $remedy = new RouteRemedy('contact_edit', [], [
            Finding::error(AbstractRequiredFieldsChecker::CODE_FIELD_MISSING, ['path' => 'name']),
            Finding::error('billing.country_unsupported', ['path' => 'address.country'], 'billing'),
        ]);

        return Request::create(
            '/contact?' . http_build_query(GateHelper::buildRouteParameters($remedy, self::DESTINATION)),
            $method
        );
    }

    private function processor(Request $request): ContactFormProcessor
    {
        self::bootKernel();
        $container = self::getContainer();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $processor = new ContactFormProcessor(
            $container->get('form.factory'),
            $requestStack,
            $container->get('router'),
        );
        $processor->setEntityManager($this->createStub(EntityManagerInterface::class));

        return $processor;
    }
}
