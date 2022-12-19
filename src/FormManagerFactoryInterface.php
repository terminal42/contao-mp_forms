<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle;

interface FormManagerFactoryInterface
{
    public function forFormId(int $id): FormManager;
}
