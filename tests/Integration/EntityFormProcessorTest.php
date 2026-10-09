<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Tests\Fixtures\App\Model\Contact;
use Wexample\SymfonyForms\Tests\Fixtures\App\Service\FormProcessor\ContactFormProcessor;

/**
 * A valid entity form saves its entity; an error can be put on one field.
 */
class EntityFormProcessorTest extends KernelTestCase
{
    public function testAValidFormSavesItsEntity(): void
    {
        self::bootKernel();
        $contact = new Contact();

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())->method('persist')->with($this->identicalTo($contact));
        $entityManager->expects($this->once())->method('flush');

        $processor = $this->processor($entityManager);
        $form = $processor->createForm($contact);
        $form->submit(['name' => '  Ada  ', 'phone' => '', 'newsletter' => '1']);
        $this->assertTrue($form->isValid());

        $processor->onValid($form);

        $this->assertSame('Ada', $contact->name, 'beforeSave() ran first.');
        $this->assertSame(['type' => AbstractFormProcessor::ACTION_DEFAULT], $processor->getSuccessAction());
        $this->assertNotNull($processor->getNotification());
    }

    public function testAnErrorIsPutOnTheFieldItConcerns(): void
    {
        self::bootKernel();
        $processor = $this->processor($this->createStub(EntityManagerInterface::class));
        $form = $processor->createForm(new Contact());
        $form->submit(['name' => 'Ada']);

        $processor->refuseName($form);

        $this->assertCount(0, $form->getErrors());
        $error = $form->get('name')->getErrors()[0];
        $this->assertSame('@form::field.name.error.taken', $error->getMessage());
        $this->assertSame(['%name%' => 'Ada'], $error->getMessageParameters());
    }

    private function processor(EntityManagerInterface $entityManager): ContactFormProcessor
    {
        $container = self::getContainer();
        $processor = new ContactFormProcessor(
            $container->get('form.factory'),
            new RequestStack(),
            $container->get('router'),
        );
        $processor->setEntityManager($entityManager);

        return $processor;
    }
}
