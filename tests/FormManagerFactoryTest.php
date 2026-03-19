<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Test;

use Codefog\HasteBundle\UrlParser;
use Contao\CoreBundle\Framework\ContaoFramework;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\MultipageFormsBundle\FormManager;
use Terminal42\MultipageFormsBundle\FormManagerFactory;
use Terminal42\MultipageFormsBundle\Step\StepDataCollection;
use Terminal42\MultipageFormsBundle\Storage\FormManagerAwareInterface;
use Terminal42\MultipageFormsBundle\Storage\FormManagerAwareTrait;
use Terminal42\MultipageFormsBundle\Storage\StorageInterface;

final class FormManagerFactoryTest extends TestCase
{
    public function testKeepsSameInstance(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $factory = new FormManagerFactory(
            $this->createStub(ContaoFramework::class),
            $requestStack,
            $this->createStub(UrlParser::class),
        );

        $manager = $factory->forFormId(42);

        $this->assertInstanceOf(FormManager::class, $manager);

        $manager2 = $factory->forFormId(42);

        $this->assertSame($manager, $manager2);
    }

    public function testCannotCreateManagerIfNoRequest(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot instantiate a FormManager without a request.');

        $factory = new FormManagerFactory(
            $this->createStub(ContaoFramework::class),
            new RequestStack(),
            $this->createStub(UrlParser::class),
        );

        $factory->forFormId(42);
    }

    public function testCustomStorage(): void
    {
        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $storage = new class() implements StorageInterface, FormManagerAwareInterface {
            use FormManagerAwareTrait;

            public function getFormManager(): FormManager
            {
                return $this->formManager;
            }

            public function storeData(string $storageIdentifier, StepDataCollection $stepDataCollection): void
            {
            }

            public function getData(string $storageIdentifier): StepDataCollection
            {
                return new StepDataCollection();
            }
        };

        $factory = new FormManagerFactory(
            $this->createStub(ContaoFramework::class),
            $requestStack,
            $this->createStub(UrlParser::class),
        );

        $factory->setStorage($storage);

        $manager = $factory->forFormId(42);

        $this->assertSame($manager, $storage->getFormManager());
    }
}
