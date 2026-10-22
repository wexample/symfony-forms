<?php

namespace Wexample\SymfonyForms\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Wexample\SymfonyForms\Tests\Fixtures\App\Form\SteeringForm;

/**
 * A form that steers a view is sent when a control settles, not when a button
 * is pressed. The decision is the form's, said once on the opening tag; the
 * listener reading it lives in the loader's form, which owns the element.
 */
class SubmitOnChangeTest extends KernelTestCase
{
    protected function setUp(): void
    {
        self::bootKernel();
    }

    public function testASteeringFormSaysSoOnItsOpeningTag(): void
    {
        $this->assertStringContainsString('data-submit-on-change', $this->renderStart());
    }

    public function testAFormWaitsForItsButtonUnlessAsked(): void
    {
        $this->assertStringNotContainsString(
            'data-submit-on-change',
            $this->renderStart(['submit_on_change' => false])
        );
    }

    /**
     * Only the opening tag changes: the fields are the ones any form draws.
     */
    public function testTheFieldsAreUntouched(): void
    {
        $html = $this->renderStart();

        $this->assertStringContainsString('<form', $html);
        $this->assertStringNotContainsString('type="submit"', $html);
    }

    private function renderStart(array $options = []): string
    {
        $form = self::getContainer()
            ->get('form.factory')
            ->create(SteeringForm::class, null, $options);

        return self::getContainer()
            ->get('twig')
            ->createTemplate('{{ form_start(form) }}')
            ->render(['form' => $form->createView()]);
    }
}
