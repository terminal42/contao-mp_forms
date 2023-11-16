<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator;

use Terminal42\MultipageFormsBundle\FormManager;

class FixedSessionReferenceGenerator implements SessionReferenceGeneratorInterface
{
    public function __construct(private readonly string $identifier)
    {
    }

    public function generate(FormManager $manager): string
    {
        return $this->identifier;
    }
}
