<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator;

use Terminal42\MultipageFormsBundle\FormManager;

interface SessionReferenceGeneratorInterface
{
    public function generate(FormManager $manager): string;
}
