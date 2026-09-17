<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * A field whose value is a record, picked by searching for it.
 *
 * What the form carries is an identifier, like any text field; what the person
 * sees is a search box narrowed to one kind of record. The kind is a string —
 * the name `wexample/symfony-search` answers with, `invoice` for an Invoice —
 * and this package names it without knowing it: `symfony-api` requires this
 * one, and search requires the api, so a dependency the other way would close
 * a circle. Install `wexample/symfony-search` for the field to have anything
 * to offer.
 */
class EntitySearchInputType extends AbstractType
{
    use FieldOptionsTrait;

    /** Which kind of record the field asks for. */
    public const string OPTION_ENTITY_TYPE = 'entity_type';

    /** Where the search is made from, which decides what it may answer. */
    public const string OPTION_SEARCH_CONTEXT = 'search_context';

    /** What to show for the value the field opens with, an id saying nothing. */
    public const string OPTION_SELECTED_TITLE = 'selected_title';

    public const string OPTION_SELECTED_SUBTITLE = 'selected_subtitle';

    public const string OPTION_SELECTED_ICON = 'selected_icon';

    public const string SEARCH_CONTEXT_DEFAULT = 'form_field';

    public function getParent(): string
    {
        return TextType::class;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault(self::OPTION_ENTITY_TYPE, null);
        $resolver->setAllowedTypes(self::OPTION_ENTITY_TYPE, ['null', 'string']);

        $resolver->setDefault(self::OPTION_SEARCH_CONTEXT, self::SEARCH_CONTEXT_DEFAULT);
        $resolver->setAllowedTypes(self::OPTION_SEARCH_CONTEXT, 'string');

        // `TextType` has no placeholder — a text field shows none — and this
        // one is a search box, which has nothing else to show while empty.
        // `true` reads it from the field's own translations, as a label does.
        $resolver->setDefault('placeholder', null);
        $resolver->setAllowedTypes('placeholder', ['null', 'bool', 'string']);

        foreach ([self::OPTION_SELECTED_TITLE, self::OPTION_SELECTED_SUBTITLE, self::OPTION_SELECTED_ICON] as $option) {
            $resolver->setDefault($option, null);
            $resolver->setAllowedTypes($option, ['null', 'string']);
        }
    }

    public function buildView(
        FormView $view,
        FormInterface $form,
        array $options
    ): void {
        parent::buildView($view, $form, $options);

        foreach ([
            self::OPTION_ENTITY_TYPE,
            self::OPTION_SEARCH_CONTEXT,
            self::OPTION_SELECTED_TITLE,
            self::OPTION_SELECTED_SUBTITLE,
            self::OPTION_SELECTED_ICON,
        ] as $option) {
            $view->vars[$option] = $options[$option];
        }
    }
}
