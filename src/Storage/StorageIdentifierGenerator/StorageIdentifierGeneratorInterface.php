<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator;

use Terminal42\MultipageFormsBundle\FormManager;

interface StorageIdentifierGeneratorInterface
{
    public function generate(FormManager $manager): string;
}
