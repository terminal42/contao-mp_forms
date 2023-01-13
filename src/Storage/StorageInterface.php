<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage;

use Terminal42\MultipageFormsBundle\Step\StepDataCollection;

interface StorageInterface
{
    public function storeData(string $storageIdentifier, StepDataCollection $stepDataCollection): void;

    public function getData(string $storageIdentifier): StepDataCollection;
}
