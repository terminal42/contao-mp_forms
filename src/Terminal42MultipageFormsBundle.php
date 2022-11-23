<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class Terminal42MultipageFormsBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
