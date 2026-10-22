<?php

namespace Wexample\SymfonyForms\Attribute;

use Attribute;

/**
 * Marks an entity that is edited through a form.
 *
 * Filestate scaffolds the form and its processor. The optional name prefixes
 * both — `#[EntityForm('create')]` gives `CreateAppForm` and
 * `CreateAppFormProcessor` — because a form bound by `data_class` already
 * serves an existing record and a new one, so a second one is only worth a
 * class when the fields themselves differ.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class EntityForm
{
    public function __construct(
        public readonly ?string $name = null
    ) {
    }
}
