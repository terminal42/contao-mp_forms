<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator;

use Terminal42\MultipageFormsBundle\FormManager;

class SessionReferenceGenerator implements SessionReferenceGeneratorInterface
{
    public function generate(FormManager $manager): string
    {
        return bin2hex(random_bytes(16));
    }
}
