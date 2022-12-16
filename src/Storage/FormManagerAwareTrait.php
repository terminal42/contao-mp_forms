<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage;

use Terminal42\MultipageFormsBundle\FormManager;

trait FormManagerAwareTrait
{
    private FormManager $formManager;

    public function setFormManager(FormManager $formManager): void
    {
        $this->formManager = $formManager;
    }
}
