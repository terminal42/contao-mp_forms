<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Storage;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Terminal42\MultipageFormsBundle\Step\StepDataCollection;

class SessionStorage implements StorageInterface
{
    final public const SESSION_KEY = 'contao.mp_forms';

    public function __construct(private readonly Request $request)
    {
    }

    public function storeData(string $storageIdentifier, StepDataCollection $stepDataCollection): void
    {
        $this->writeToSession($storageIdentifier, $stepDataCollection);
    }

    public function getData(string $storageIdentifier): StepDataCollection
    {
        return $this->readFromSession($storageIdentifier);
    }

    private function writeToSession(string $storageIdentifier, StepDataCollection $collection): void
    {
        if (null === ($session = $this->getSession())) {
            return;
        }

        $session->set($this->getSessionKey($storageIdentifier), $collection);
    }

    private function readFromSession(string $storageIdentifier, bool $checkPrevious = false): StepDataCollection
    {
        $empty = new StepDataCollection();

        if (null === ($session = $this->getSession($checkPrevious))) {
            return $empty;
        }

        return $session->get($this->getSessionKey($storageIdentifier), $empty);
    }

    private function getSessionKey(string $storageIdentifier): string
    {
        return self::SESSION_KEY.'.'.$storageIdentifier;
    }

    private function getSession(bool $checkPrevious = false): SessionInterface|null
    {
        if ($checkPrevious && !$this->request->hasPreviousSession()) {
            return null;
        }

        if (!$this->request->hasSession()) {
            return null;
        }

        return $this->request->getSession();
    }
}
