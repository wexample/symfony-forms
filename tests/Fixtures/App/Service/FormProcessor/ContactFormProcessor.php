<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Service\FormProcessor;

use Symfony\Component\Form\FormInterface;
use Wexample\SymfonyForms\Service\FormProcessor\AbstractEntityFormProcessor;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\ContactForm;

class ContactFormProcessor extends AbstractEntityFormProcessor
{
    public static function getFormClass(): string
    {
        return ContactForm::class;
    }

    /** A name already taken: the error goes to the field. */
    public function refuseName(FormInterface $form): void
    {
        $this->addFieldError($form, 'name', 'taken', ['%name%' => $form->get('name')->getData()]);
    }

    protected function beforeSave(object $entity, FormInterface $form): void
    {
        $entity->name = trim((string) $entity->name);
    }
}
