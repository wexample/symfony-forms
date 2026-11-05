<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Wexample\SymfonyForms\Form\Type\SearchSelectInputType;

class SearchSelectInputTypeTest extends TestCase
{
    public function testAValueNoOptionHoldsIsTakenAsItIs(): void
    {
        // The field does not hold every option: the application checks.
        $form = Forms::createFormFactoryBuilder()->getFormFactory()
            ->create(SearchSelectInputType::class, null, ['options' => ['a']]);

        $form->submit('conversation');

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('conversation', $form->getData());
    }

    public function testTheValueIsShownByTheLabelItsOptionsGive(): void
    {
        $vars = Forms::createFormFactoryBuilder()->getFormFactory()
            ->create(SearchSelectInputType::class, 'b', ['options' => ['a' => 'Alpha', 'b' => 'Beta'], 'options_url' => '/choices'])
            ->createView()->vars;

        $this->assertSame([['value' => 'a', 'label' => 'Alpha'], ['value' => 'b', 'label' => 'Beta']], $vars['options']);
        $this->assertSame('/choices', $vars['options_url']);
        $this->assertSame('Beta', $vars['value_label']);
    }

    public function testALabelGivenWinsAndNoneIsNull(): void
    {
        $factory = Forms::createFormFactoryBuilder()->getFormFactory();

        $this->assertSame('Conversation', $factory->create(SearchSelectInputType::class, 'conversation', ['value_label' => 'Conversation'])->createView()->vars['value_label']);
        $this->assertNull($factory->create(SearchSelectInputType::class, 'conversation')->createView()->vars['value_label']);
    }
}
