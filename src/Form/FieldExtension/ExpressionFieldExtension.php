<?php

namespace Wexample\SymfonyForms\Form\FieldExtension;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ResetInterface;
use Wexample\SymfonyForms\Form\Extension\FieldExtensionsTypeExtension;

/**
 * An expression in place of the value — a template computing it on each
 * run —: the design system switches the field to an editor of `language` and
 * back, and starts it as an expression when the value matches `pattern`.
 *
 * The value is sent under the field's name, as text, whichever it is. A
 * number's or a switch's transformers would refuse `{{ model }}`: an
 * expression held is kept from them and shown by the drawn field as it is;
 * an expression sent is read by a text field put in the field's place before
 * it is given it. What is held is then the expression, as text, which the
 * application reads as it must anyway.
 */
class ExpressionFieldExtension extends AbstractFieldExtension implements ResetInterface
{
    /** @var \WeakMap<FormInterface, string> The expression each field holds, kept from its transformers. */
    private \WeakMap $held;

    public function __construct()
    {
        $this->held = new \WeakMap();
    }

    public static function getName(): string
    {
        return 'expression';
    }

    /** A request's forms are gone with it; nothing of them is kept for the next. */
    public function reset(): void
    {
        $this->held = new \WeakMap();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(['language', 'pattern']);
        $resolver->setDefault('rows', 2);
        $resolver->setAllowedTypes('language', 'string');
        $resolver->setAllowedTypes('pattern', 'string');
        $resolver->setAllowedTypes('rows', 'int');
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options): void {
            $data = $event->getData();

            if (self::matches($data, $options)) {
                $this->held[$event->getForm()] = $data;
                $event->setData(null);
            } else {
                unset($this->held[$event->getForm()]);
            }
        });
    }

    /** An expression sent: a text field in the field's place, the same name, reads it as it is. */
    public function prepareSubmit(FormInterface $parent, FormInterface $field, mixed $submitted, array $options): void
    {
        if (! self::matches($submitted, $options) || $field->getConfig()->getType()->getInnerType() instanceof TextType) {
            return;
        }

        $config = $field->getConfig();
        $parent->add($field->getName(), TextType::class, [
            'label' => $config->getOption('label'),
            'required' => false,
            'mapped' => $config->getOption('mapped'),
            'property_path' => $config->getOption('property_path'),
            'row_attr' => $config->getOption('row_attr'),
            FieldExtensionsTypeExtension::OPTION => $config->getOption(FieldExtensionsTypeExtension::OPTION),
        ]);
    }

    /** The drawn field shows the expression, which its transformers never saw. */
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        if (isset($this->held[$form])) {
            $view->vars['value'] = $this->held[$form];
        }
    }

    private static function matches(mixed $value, array $options): bool
    {
        return is_string($value) && preg_match('/'.str_replace('/', '\\/', $options['pattern']).'/', $value) === 1;
    }
}
