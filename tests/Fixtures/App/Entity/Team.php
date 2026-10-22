<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Team
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    public function __construct(
        #[ORM\Column]
        public string $name,
    ) {
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
