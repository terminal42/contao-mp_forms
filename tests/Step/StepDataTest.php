<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Test\Step;

use PHPUnit\Framework\TestCase;
use Terminal42\MultipageFormsBundle\Step\FileParameterBag;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;
use Terminal42\MultipageFormsBundle\Step\StepData;

class StepDataTest extends TestCase
{
    /**
     * @dataProvider parametersDataProvider
     */
    public function testSubmitted(array $data): void
    {
        $stepData = StepData::create(1);

        $parameters = new ParameterBag($data);

        $this->assertTrue($stepData->getSubmitted()->empty());

        $stepData = $stepData->withSubmitted($parameters);
        $this->assertTrue($parameters->equals($stepData->getSubmitted()));
    }

    public function testFiles(): void
    {
        $stepData = StepData::create(1);

        $parameters = new FileParameterBag([
            'file_upload' => [
                [
                    'tmp_name' => '/tmp/file.tmp',
                ],
            ],
        ]);

        $this->assertTrue($stepData->getFiles()->empty());

        $stepData = $stepData->withFiles($parameters);
        $this->assertTrue($parameters->equals($stepData->getFiles()));
    }

    /**
     * @dataProvider parametersDataProvider
     */
    public function testOriginalData(array $data): void
    {
        $stepData = StepData::create(1);

        $parameters = new ParameterBag($data);

        $this->assertTrue($stepData->getOriginalPostData()->empty());

        $stepData = $stepData->withOriginalPostData($parameters);
        $this->assertTrue($parameters->equals($stepData->getOriginalPostData()));
    }

    /**
     * @dataProvider parametersDataProvider
     */
    public function testLabels(array $data): void
    {
        $stepData = StepData::create(1);

        $parameters = new ParameterBag($data);

        $this->assertTrue($stepData->getLabels()->empty());

        $stepData = $stepData->withLabels($parameters);
        $this->assertTrue($parameters->equals($stepData->getLabels()));
    }

    public static function parametersDataProvider(): iterable
    {
        yield [
            [
                'value_a' => 'a',
                'value_b' => [
                    'nested' => 'old',
                ],
            ],
        ];
    }
}
