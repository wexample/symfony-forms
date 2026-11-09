<?php

namespace Wexample\SymfonyForms\Exception;

/**
 * A form several processors handle, looked up by its name or its class: there
 * is no single processor to answer.
 */
class AmbiguousFormProcessorException extends \LogicException
{
    /**
     * @param list<string> $processors
     */
    public function __construct(string $form, array $processors)
    {
        parent::__construct(sprintf(
            'Form "%s" is handled by several processors (%s), so it cannot be found by its name or class: '
            . 'post it to the page that picks one through #[FormProcessor], or give each processor its own form.',
            $form,
            implode(', ', $processors)
        ));
    }
}
