<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator;

use Terminal42\MultipageFormsBundle\FormManager;

class FixedStorageIdentifierGenerator implements StorageIdentifierGeneratorInterface
{
    public function __construct(private readonly string $identifier)
    {
    }

    public function generate(FormManager $manager): string
    {
        return $this->identifier;
    }
}
