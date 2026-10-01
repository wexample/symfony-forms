<?php

namespace Wexample\SymfonyForms\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Type\DisplayValueType;

/**
 * The three defaults that make a display value harmless, each of them on its
 * own enough to stop a post: unmapped, never required, never bound.
 */
class DisplayValueTypeTest extends TestCase
{
    public function testItIsNeitherMappedNorRequiredNorBound(): void
    {
        $resolver = new OptionsResolver();
        $resolver->setDefined(['mapped', 'required', 'disabled']);
        (new DisplayValueType())->configureOptions($resolver);

        $options = $resolver->resolve();

        $this->assertFalse($options['mapped']);
        $this->assertFalse($options['required']);
        $this->assertTrue($options['disabled']);
    }

    public function testItRendersThroughItsOwnBlock(): void
    {
        $this->assertSame('display_value', (new DisplayValueType())->getBlockPrefix());
        $this->assertSame(TextType::class, (new DisplayValueType())->getParent());
    }
}
