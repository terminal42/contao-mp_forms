<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Test;

use Codefog\HasteBundle\UrlParser;
use Contao\Config;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Model\Collection;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Terminal42\MultipageFormsBundle\FormManagerFactory;
use Terminal42\MultipageFormsBundle\Step\StepDataCollection;
use Terminal42\MultipageFormsBundle\Storage\InMemoryStorage;
use Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator\FixedSessionReferenceGenerator;
use Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator\FixedStorageIdentifierGenerator;
use Terminal42\MultipageFormsBundle\Storage\StorageInterface;

abstract class AbstractTestCase extends ContaoTestCase
{
    protected const STORAGE_IDENTIFIER = 'storage-identifier';

    protected const SESSION_IDENTIFIER = 'session-identifier';

    protected function createFormFieldsForValidConfiguration(): Collection
    {
        $formFieldsModels = [
            $this->mockClassWithProperties(FormFieldModel::class, [
                'id' => 1,
                'pid' => 42,
                'tstamp' => 1,
                'type' => 'text',
            ]),
            $this->mockClassWithProperties(FormFieldModel::class, [
                'id' => 2,
                'pid' => 42,
                'tstamp' => 1,
                'type' => 'mp_form_pageswitch',
            ]),
            $this->mockClassWithProperties(FormFieldModel::class, [
                'id' => 3,
                'pid' => 42,
                'tstamp' => 1,
                'type' => 'text',
            ]),
            $this->mockClassWithProperties(FormFieldModel::class, [
                'id' => 4,
                'pid' => 42,
                'tstamp' => 1,
                'type' => 'mp_form_pageswitch',
            ]),
            $this->mockClassWithProperties(FormFieldModel::class, [
                'id' => 5,
                'pid' => 42,
                'tstamp' => 1,
                'type' => 'text',
            ]),
            $this->mockClassWithProperties(FormFieldModel::class, [
                'id' => 6,
                'pid' => 42,
                'tstamp' => 1,
                'type' => 'mp_form_pageswitch',
            ]),
        ];

        return new Collection($formFieldsModels, 'tl_form_field');
    }

    protected function createStorage(StepDataCollection|null $initialData = null): InMemoryStorage
    {
        $storage = new InMemoryStorage();

        if ($initialData) {
            $storage->storeData(self::STORAGE_IDENTIFIER, $initialData);
        }

        return $storage;
    }

    protected function createFactory(FormModel $formModel, Collection $formFields, StorageInterface $storage, int $step = 0): FormManagerFactory
    {
        $stack = new RequestStack();
        $request = Request::create('https://www.example.com/form');

        if ($step) {
            $request->query->set('step', $step);
        }

        $request->setSession(new Session(new MockArraySessionStorage()));
        $stack->push($request);

        $formModelAdapter = $this->mockAdapter(['findById']);
        $formModelAdapter
            ->method('findById')
            ->willReturn($formModel)
        ;

        $formFieldModel = $this->mockAdapter(['findPublishedByPid']);
        $formFieldModel
            ->method('findPublishedByPid')
            ->willReturn($formFields)
        ;

        $framework = $this->mockContaoFramework([
            FormModel::class => $formModelAdapter,
            FormFieldModel::class => $formFieldModel,
            System::class => $this->mockAdapter([]),
            Config::class => '', // Not needed in our tests but required for the ContaoTestCase not to fail
        ]);

        $factory = new FormManagerFactory(
            $framework,
            $stack,
            new UrlParser(),
        );

        $factory->setStorage($storage);
        $factory->setStorageIdentifierGenerator(new FixedStorageIdentifierGenerator(self::STORAGE_IDENTIFIER));
        $factory->setSessionReferenceGenerator(new FixedSessionReferenceGenerator(self::SESSION_IDENTIFIER));

        return $factory;
    }
}
