<?php

namespace Wexample\SymfonyForms\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\FieldExtension\FieldExtensionInterface;

/**
 * The one option every field takes for what is attached to it whatever its
 * kind (`extensions`, by name — FieldExtensionInterface): checked against
 * what each extension takes, handed to the form's building, and to the view
 * twice — `extensions` as the definition carries it, `extensions_drawn` as
 * the drawn field's components are given it.
 */
class FieldExtensionsTypeExtension extends AbstractTypeExtension
{
    public const string OPTION = 'extensions';

    /** @var array<string, FieldExtensionInterface> */
    private readonly array $extensions;

    /** @param iterable<FieldExtensionInterface> $extensions */
    public function __construct(iterable $extensions)
    {
        $byName = [];

        foreach ($extensions as $extension) {
            $byName[$extension::getName()] = $extension;
        }

        $this->extensions = $byName;
    }

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION, []);
        $resolver->setAllowedTypes(self::OPTION, 'array');
        $resolver->setNormalizer(self::OPTION, function (Options $options, array $configured): array {
            $resolved = [];

            foreach ($configured as $name => $config) {
                $extension = $this->extensions[$name] ?? throw new InvalidOptionsException(sprintf(
                    'No field extension "%s": %s.',
                    $name,
                    $this->extensions ? implode(', ', array_keys($this->extensions)) : 'none is declared'
                ));
                $own = new OptionsResolver();
                $extension->configureOptions($own);
                $resolved[$name] = $own->resolve(is_array($config) ? $config : []);
            }

            return $resolved;
        });
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($options[self::OPTION] as $name => $config) {
            $this->extensions[$name]->buildForm($builder, $config);
        }

        // A form holding fields: before it hands each what was sent for it,
        // the field's extensions may change how it will read it.
        if ($builder->getCompound()) {
            $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
                $parent = $event->getForm();
                $submitted = is_array($event->getData()) ? $event->getData() : [];

                foreach ($parent->all() as $name => $field) {
                    foreach ($field->getConfig()->getOption(self::OPTION, []) as $extension => $config) {
                        $this->extensions[$extension]->prepareSubmit($parent, $field, $submitted[$name] ?? null, $config);
                    }
                }
            });
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars[self::OPTION] = [];
        $view->vars[self::OPTION.'_drawn'] = [];

        foreach ($options[self::OPTION] as $name => $config) {
            $this->extensions[$name]->buildView($view, $form, $config);
            $definition = $this->extensions[$name]->buildDefinition($config, $form);
            $view->vars[self::OPTION][$name] = $definition;
            $drawn = $this->extensions[$name]->buildDrawn($definition, $form);

            if ([] !== $drawn) {
                $view->vars[self::OPTION.'_drawn'][$name] = $drawn;
            }
        }
    }
}
