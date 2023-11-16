<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle;

use Codefog\HasteBundle\UrlParser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\MultipageFormsBundle\Storage\FormManagerAwareInterface;
use Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator\SessionReferenceGenerator;
use Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator\SessionReferenceGeneratorInterface;
use Terminal42\MultipageFormsBundle\Storage\SessionStorage;
use Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator\StorageIdentifierGenerator;
use Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator\StorageIdentifierGeneratorInterface;
use Terminal42\MultipageFormsBundle\Storage\StorageInterface;

class FormManagerFactory implements FormManagerFactoryInterface
{
    private StorageInterface|null $storage = null;

    private StorageIdentifierGeneratorInterface|null $storageIdentifierGenerator = null;

    private SessionReferenceGeneratorInterface|null $sessionReferenceGenerator = null;

    /**
     * @var array<int, FormManager>
     */
    private array $managers = [];

    public function __construct(
        private readonly ContaoFramework $contaoFramework,
        private readonly RequestStack $requestStack,
        private readonly UrlParser $urlParser,
    ) {
    }

    public function setStorage(StorageInterface|null $storage): self
    {
        $this->storage = $storage;

        return $this;
    }

    public function setStorageIdentifierGenerator(StorageIdentifierGeneratorInterface|null $storageIdentifierGenerator): self
    {
        $this->storageIdentifierGenerator = $storageIdentifierGenerator;

        return $this;
    }

    public function setSessionReferenceGenerator(SessionReferenceGeneratorInterface|null $sessionReferenceGenerator): self
    {
        $this->sessionReferenceGenerator = $sessionReferenceGenerator;

        return $this;
    }

    public function forFormId(int $id): FormManager
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            throw new \LogicException('Cannot instantiate a FormManager without a request.');
        }

        if (isset($this->managers[$id])) {
            return $this->managers[$id];
        }

        $storage = $this->storage ?? new SessionStorage($request);
        $storageIdentifierGenerator = $this->storageIdentifierGenerator ?? new StorageIdentifierGenerator();
        $sessionReferenceGenerator = $this->sessionReferenceGenerator ?? new SessionReferenceGenerator();

        $manager = $this->managers[$id] = new FormManager(
            $id,
            $request,
            $this->contaoFramework,
            $storage,
            $storageIdentifierGenerator,
            $sessionReferenceGenerator,
            $this->urlParser,
        );

        if ($storage instanceof FormManagerAwareInterface) {
            $storage->setFormManager($manager);
        }

        return $manager;
    }
}
