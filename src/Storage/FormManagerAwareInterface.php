<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage;

use Terminal42\MultipageFormsBundle\FormManager;

interface FormManagerAwareInterface
{
    public function setFormManager(FormManager $formManager): void;
}
