<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\Processor;

use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\ContactForm;

/**
 * A second processor claiming the form `ContactFormProcessor` handles.
 */
class SecondContactFormProcessor extends AbstractFormProcessor
{
    public static function getFormClass(): string
    {
        return ContactForm::class;
    }
}
