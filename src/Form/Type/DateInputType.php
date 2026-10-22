<?php

namespace Wexample\SymfonyForms\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Wexample\SymfonyForms\Form\Traits\FieldOptionsTrait;

class DateInputType extends DateType
{
    use FieldOptionsTrait;

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'widget' => 'single_text',
            'html5' => true,
        ]);

        // A day has no time, so it has no zone to be shown in: converting it
        // from the model zone to the reader's moves midnight across a date
        // line, and the 3rd typed in Paris is stored as the 2nd in UTC. An
        // application setting `view_timezone` for its times would do that to
        // every date field, so a date field reads and writes in the model zone.
        $resolver->setNormalizer(
            'view_timezone',
            static fn (Options $options): ?string => $options['model_timezone']
        );
    }
}
