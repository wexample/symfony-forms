<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * Pairs of words, a key and its value — metadata, headers, parameters —: a
 * record `['lang' => 'fr']` on the model's side, a list of pairs in the
 * order they were written on the field's, posted each under its index
 * (`form[field][0][key]`, `form[field][0][value]`), as the json-schema norm
 * says (`key_value_input.schema.json`).
 *
 * Read back, a line left blank is dropped; a value without its key, or a key
 * written twice, is refused rather than dropped — what the reader typed is
 * not lost in silence. Values are text: a record holding a number or a
 * boolean is read as its text, and given back so.
 */
class KeyValueInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return FormType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // One field, posted as a list: not a form of its own, and a list
            // is what it takes (Symfony refuses an array to a single field
            // unless it says `multiple`).
            'compound' => false,
            'multiple' => true,
            'empty_data' => [],
            'key_placeholder' => null,
            'value_placeholder' => null,
            'invalid_message' => 'Each value needs its key, and each key is written once.',
        ]);
        $resolver->setAllowedValues('multiple', [true]);
        $resolver->setAllowedTypes('key_placeholder', ['null', 'string']);
        $resolver->setAllowedTypes('value_placeholder', ['null', 'string']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addViewTransformer(new CallbackTransformer(
            static fn (?array $record): array => self::toPairs($record ?? []),
            static fn (mixed $pairs): ?array => self::toRecord($pairs),
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['key_placeholder'] = $options['key_placeholder'];
        $view->vars['value_placeholder'] = $options['value_placeholder'];
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    public static function toPairs(array $record): array
    {
        $pairs = [];

        foreach ($record as $key => $value) {
            $pairs[] = ['key' => (string) $key, 'value' => self::toText($value)];
        }

        return $pairs;
    }

    /**
     * @return array<string, string>|null
     */
    public static function toRecord(mixed $pairs): ?array
    {
        if (null === $pairs || '' === $pairs) {
            return null;
        }

        if (! is_array($pairs)) {
            throw new TransformationFailedException('Pairs were expected as a list.');
        }

        $record = [];

        foreach ($pairs as $pair) {
            if (! is_array($pair)) {
                throw new TransformationFailedException('A pair was expected as a key and a value.');
            }

            $key = $pair['key'] ?? '';
            $value = $pair['value'] ?? '';

            if (! is_scalar($key) || ! is_scalar($value)) {
                throw new TransformationFailedException('A key and its value are text.');
            }

            $key = trim((string) $key);
            $value = (string) $value;

            // A line left blank: nothing was written.
            if ('' === $key && '' === trim($value)) {
                continue;
            }

            if ('' === $key) {
                throw new TransformationFailedException('A value was written without its key.');
            }

            if (array_key_exists($key, $record)) {
                throw new TransformationFailedException(sprintf('The key "%s" is written twice.', $key));
            }

            $record[$key] = $value;
        }

        return [] === $record ? null : $record;
    }

    private static function toText(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'true' : 'false',
            is_scalar($value) => (string) $value,
            null === $value => '',
            default => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        };
    }
}
