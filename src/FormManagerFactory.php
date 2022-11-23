<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle;

use Codefog\HasteBundle\UrlParser;
use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\MultipageFormsBundle\Storage\FormManagerAwareInterface;
use Terminal42\MultipageFormsBundle\Storage\SessionStorage;
use Terminal42\MultipageFormsBundle\Storage\StorageInterface;

class FormManagerFactory
{
    private StorageInterface|null $storage = null;

    /**
     * @var array<int, FormManager>
     */
    private array $managers = [];

    public function __construct(
        private ContaoFramework $contaoFramework,
        private RequestStack $requestStack,
        private UrlParser $urlParser,
    ) {
    }

    public function setStorage(StorageInterface|null $storage): self
    {
        $this->storage = $storage;

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

        $manager = $this->managers[$id] = new FormManager(
            $id,
            $request,
            $this->contaoFramework,
            $storage,
            $this->urlParser
        );

        if ($storage instanceof FormManagerAwareInterface) {
            $storage->setFormManager($manager);
        }

        return $manager;
    }
}
