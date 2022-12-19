<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage;

use Terminal42\MultipageFormsBundle\Step\StepDataCollection;

class InMemoryStorage implements StorageInterface
{
    public function __construct(private array $data = [])
    {
    }

    public function storeData(string $storageIdentifier, StepDataCollection $stepDataCollection): void
    {
        $this->data[$storageIdentifier] = $stepDataCollection;
    }

    public function getData(string $storageIdentifier): StepDataCollection
    {
        return $this->data[$storageIdentifier] ?? new StepDataCollection();
    }
}
