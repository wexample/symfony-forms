<?php

namespace Wexample\SymfonyForms\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A field that only means something for some values of another — a name
 * asked only when the scope is « named »: `visible_when`, the other field by
 * its name, the values it must hold.
 *
 *   'visible_when' => ['field' => 'scope', 'in' => ['named']]
 *
 * Shown and hidden as the other one changes, without saving; hidden, the
 * field is sent empty — whatever it held is dropped. The definition carries
 * it (`visibleWhen`), the twig theme on the field's row. The server still
 * reads what is sent as it would: it is not told the field was hidden.
 */
class VisibleWhenTypeExtension extends AbstractTypeExtension
{
    public const string OPTION = 'visible_when';

    public static function getExtendedTypes(): iterable
    {
        return [FormType::class];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION, null);
        $resolver->setAllowedTypes(self::OPTION, ['null', 'array']);
        $resolver->setNormalizer(self::OPTION, static function (Options $options, ?array $when): ?array {
            if (null === $when) {
                return null;
            }

            if (! is_string($when['field'] ?? null) || '' === $when['field'] || ! is_array($when['in'] ?? null)) {
                throw new InvalidOptionsException('"visible_when" takes ["field" => <the other field\'s name>, "in" => [<its values>]].');
            }

            return ['field' => $when['field'], 'in' => array_values(array_map('strval', $when['in']))];
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars[self::OPTION] = $options[self::OPTION];
    }
}
