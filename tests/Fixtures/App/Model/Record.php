<?php

namespace Wexample\SymfonyForms\Tests\Fixtures\App\Model;

/**
 * What `RecordForm` edits. A plain object and not an entity: whether a value
 * survives a forged post is decided by the form, before Doctrine is reached.
 */
class Record
{
    public ?string $name = null;

    public ?string $email = null;

    public ?string $site = null;

    public ?string $notes = null;

    public ?string $password = null;

    public ?float $weight = null;

    public ?float $count = null;

    public ?\DateTimeInterface $dateBorn = null;

    public ?\DateTimeInterface $dateSeen = null;

    public ?\DateTimeInterface $timeSeen = null;

    public ?string $status = null;

    public ?string $kind = null;

    public bool $active = false;

    public ?string $code = null;

    public ?string $mood = null;
}
