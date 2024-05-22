<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Test\Step;

use PHPUnit\Framework\TestCase;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;
use Terminal42\MultipageFormsBundle\Step\StepData;
use Terminal42\MultipageFormsBundle\Step\StepDataCollection;

class StepDataCollectionTest extends TestCase
{
    public function testGetAll(): void
    {
        $stepCollection = new StepDataCollection();
        $stepCollection->set($this->createStepData(1, new ParameterBag(['foobar' => 'value'])));
        $stepCollection->set($this->createStepData(2, new ParameterBag(['foobar_2' => 'value 2'])));
        $stepCollection->set($this->createStepData(3, new ParameterBag(['foobar' => 'value 3'])));

        $expected = [
            'foobar' => 'value 3',
            'foobar_2' => 'value 2',
        ];

        $this->assertSame($expected, $stepCollection->getAllLabels());
        $this->assertSame($expected, $stepCollection->getAllSubmitted());
    }

    private function createStepData(int $step, ParameterBag $parameters): StepData
    {
        $step = StepData::create($step);
        $step = $step->withSubmitted($parameters);

        return $step->withLabels($parameters);
    }
}
