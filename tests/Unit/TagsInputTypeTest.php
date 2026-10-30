<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Forms;
use Wexample\SymfonyForms\Form\Type\TagsInputType;

class TagsInputTypeTest extends TestCase
{
    public function testTheTextIsReadAsTagsTrimmedAndOnceEach(): void
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()->create(TagsInputType::class);

        $form->submit(' first, second,,first ,third ');

        $this->assertSame(['first', 'second', 'third'], $form->getData());
    }

    public function testTheTagsAreShownJoined(): void
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()
            ->create(TagsInputType::class, ['first', 'second'], ['separator' => ';']);

        $this->assertSame('first; second', $form->createView()->vars['value']);
    }

    public function testNothingTypedIsNoTag(): void
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()->create(TagsInputType::class);

        $form->submit(null);

        $this->assertSame([], $form->getData());
    }
}
