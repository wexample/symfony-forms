<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\Processor;

use Wexample\SymfonyForms\Service\FormProcessor\AbstractFormProcessor;

/**
 * A processor whose form was never written: it cannot name its form class.
 * Outside the fixture application's scan — it would stop its container.
 */
class OrphanFormProcessor extends AbstractFormProcessor
{
}
