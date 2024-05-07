<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Test;

use Codefog\HasteBundle\UrlParser;
use Contao\Config;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\System;
use Contao\TestCase\ContaoTestCase;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\MultipageFormsBundle\FormManager;
use Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator\SessionReferenceGeneratorInterface;
use Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator\StorageIdentifierGeneratorInterface;
use Terminal42\MultipageFormsBundle\Storage\StorageInterface;

class FormManagerTest extends ContaoTestCase
{
    public function testAccessingAllDataWithoutFormFields(): void
    {
        $formModel = $this->mockClassWithProperties(FormModel::class, ['id' => 42]);
        $formModelAdapter = $this->mockAdapter(['findById']);
        $formModelAdapter
            ->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($formModel)
        ;

        $formFieldModelAdapter = $this->mockAdapter(['findPublishedByPid']);
        $formFieldModelAdapter
            ->expects($this->once())
            ->method('findPublishedByPid')
            ->with(42)
            ->willReturn([])
        ;

        $framework = $this->mockContaoFramework([
            Config::class => $this->mockAdapter(['isComplete']),
            System::class => $this->mockAdapter(['importStatic']),
            FormModel::class => $formModelAdapter,
            FormFieldModel::class => $formFieldModelAdapter,
        ]);

        $formManager = new FormManager(
            42,
            new Request(),
            $framework,
            $this->createMock(StorageInterface::class),
            $this->createMock(StorageIdentifierGeneratorInterface::class),
            $this->createMock(SessionReferenceGeneratorInterface::class),
            new UrlParser(),
        );

        $this->assertSame([], $formManager->getDataOfAllSteps()->all());
    }
}
