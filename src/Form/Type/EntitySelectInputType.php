<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * Every row of a short table offered at once, as a select: a team among ten.
 * The options are the entities themselves (`class`, `choice_label`,
 * `query_builder` as Doctrine's EntityType takes them), labelled by their data
 * and not translated. A long table is searched instead:
 * symfony-search's `EntitySearchInputType`.
 */
class EntitySelectInputType extends AbstractType
{
    use FieldOptionsTrait;

    public function getParent(): string
    {
        return EntityType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'select_input';
    }
}
