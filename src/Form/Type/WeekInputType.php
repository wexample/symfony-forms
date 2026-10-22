<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\WeekType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

/**
 * An ISO week picked as a whole, drawn by the design system's `week-input`.
 *
 * The value is the week key `2026-W29` by default — what the browser's week
 * input posts, and what `php-date`'s `DateHelper::getWeekKey()` and
 * `buildFromWeekKey()` speak — rather than Symfony's `['year', 'week']` array:
 * a period travels between the form, the query string and the formatter as one
 * string. `input: 'array'` gives the array back when a model wants it.
 */
class WeekInputType extends WeekType
{
    use FieldOptionsTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => true,
            'input' => 'string',
        ]);
    }
}
