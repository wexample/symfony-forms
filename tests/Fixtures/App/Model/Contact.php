<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * A contact, whose name may not exceed 40 characters.
 */
class Contact
{
    #[Assert\Length(max: 40)]
    public ?string $name = null;

    public ?string $phone = null;

    public bool $newsletter = false;
}
