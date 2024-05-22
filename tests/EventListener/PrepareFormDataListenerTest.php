<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Test\EventListener;

use Codefog\HasteBundle\FileUploadNormalizer;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\FormModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\MultipageFormsBundle\EventListener\PrepareFomDataListener;
use Terminal42\MultipageFormsBundle\FormManager;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;
use Terminal42\MultipageFormsBundle\Step\StepData;
use Terminal42\MultipageFormsBundle\Step\StepDataCollection;
use Terminal42\MultipageFormsBundle\Test\AbstractTestCase;

class PrepareFormDataListenerTest extends AbstractTestCase
{
    public function testDataIsStoredProperlyAndDoesNotAdjustHookParametersIfNotOnLastStep(): void
    {
        $stepData = StepData::create(0);
        $stepData = $stepData->withSubmitted(new ParameterBag(['submitted1' => 'foobar']));
        $stepData = $stepData->withLabels(new ParameterBag(['label1' => 'foobar']));
        $initialData = (new StepDataCollection())->set($stepData);
        $storage = $this->createStorage($initialData);

        $form = FormManager::createDummyForm(42);

        $factory = $this->createFactory(
            $this->mockClassWithProperties(FormModel::class, ['id' => 42]),
            $this->createFormFieldsForValidConfiguration(),
            $storage,
            1, // This mocks step=1 (page 2)
        );

        $listener = new PrepareFomDataListener($factory, $this->createMock(RequestStack::class), $this->createMock(FileUploadNormalizer::class));

        $submitted = ['submitted2' => 'foobar', 'mp_form_pageswitch' => 'continue'];
        $labels = [];
        $fields = [];
        $files = [];

        try {
            $listener($submitted, $labels, $fields, $form, $files);
        } catch (RedirectResponseException $exception) {
            $this->assertSame(
                'https://www.example.com/form?step=2&ref='.self::SESSION_IDENTIFIER,
                $exception->getResponse()->headers->get('Location'),
            );
        }

        $this->assertSame(['submitted2' => 'foobar', 'mp_form_pageswitch' => 'continue'], $submitted); // "mp_form_pageswitch" should not be removed
        $this->assertSame([], $labels); // Test we do not modify the hook parameters if not in last step

        $manager = $factory->forFormId(42);

        $this->assertSame(['submitted1' => 'foobar', 'submitted2' => 'foobar', 'mp_form_pageswitch' => 'continue'], $manager->getDataOfAllSteps()->getAllSubmitted());
        $this->assertSame(['label1' => 'foobar'], $manager->getDataOfAllSteps()->getAllLabels());
    }

    public function testDataIsStoredProperlyAndDoesAdjustHookParametersOnLastStep(): void
    {
        $stepData = StepData::create(0);
        $stepData = $stepData->withSubmitted(new ParameterBag(['submitted1' => 'foobar', 'mp_form_pageswitch' => 'continue']));
        $stepData = $stepData->withLabels(new ParameterBag(['label1' => 'foobar']));
        $stepData2 = StepData::create(1);
        $stepData2 = $stepData2->withSubmitted(new ParameterBag(['submitted2' => 'foobar', 'mp_form_pageswitch' => 'continue']));
        $initialData = (new StepDataCollection())->set($stepData)->set($stepData2);
        $storage = $this->createStorage($initialData);

        $form = FormManager::createDummyForm(42);

        $factory = $this->createFactory(
            $this->mockClassWithProperties(FormModel::class, ['id' => 42]),
            $this->createFormFieldsForValidConfiguration(),
            $storage,
            2, // This mocks step=2 (page 3 - last page)
        );

        $listener = new PrepareFomDataListener($factory, $this->createMock(RequestStack::class), $this->createMock(FileUploadNormalizer::class));

        $submitted = ['submitted3' => 'foobar', 'mp_form_pageswitch' => 'continue'];
        $labels = [];
        $fields = [];
        $files = [];

        $listener($submitted, $labels, $fields, $form, $files); // Must not redirect, so no exception

        // Submitted should now contain all values except for "mp_form_pageswitch" values
        $this->assertSame(['submitted1' => 'foobar', 'submitted2' => 'foobar', 'submitted3' => 'foobar'], $submitted);
        $this->assertSame(['label1' => 'foobar'], $labels); // Test we do not modify the hook parameters if not in last step
    }
}
