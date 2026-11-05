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

    public function testSuggestionsAreReadAsWordsAndLabelsWhateverTheirShape(): void
    {
        $this->assertSame(
            [['value' => 'urgent', 'label' => 'urgent'], ['value' => 'vip', 'label' => 'Important person'], ['value' => 'late', 'label' => 'Late']],
            TagsInputType::normalizeSuggestions(['urgent', 'vip' => 'Important person', ['value' => 'late', 'label' => 'Late'], ' ', 'urgent'])
        );
    }

    public function testTheViewHoldsTheWordsAndHowTheyAreDrawn(): void
    {
        $form = Forms::createFormFactoryBuilder()->getFormFactory()
            ->create(TagsInputType::class, ['a', 'b'], ['suggestions' => ['c'], 'suggestions_url' => '/stamps', 'tone' => 'cat-straw', 'glyph' => 'ph:bold/tag']);
        $vars = $form->createView()->vars;

        $this->assertSame(['a', 'b'], $vars['tags']);
        $this->assertSame([['value' => 'c', 'label' => 'c']], $vars['suggestions']);
        $this->assertSame('/stamps', $vars['suggestions_url']);
        $this->assertSame('cat-straw', $vars['tone']);
        $this->assertSame('ph:bold/tag', $vars['glyph']);
    }
}
