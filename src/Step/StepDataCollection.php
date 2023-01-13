<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Step;

class StepDataCollection
{
    /**
     * @var array<int, StepData>
     */
    private array $dataPerStep = [];

    public function get(int $step): StepData
    {
        if (!isset($this->dataPerStep[$step])) {
            $this->dataPerStep[$step] = StepData::create($step);
        }

        return $this->dataPerStep[$step];
    }

    public function set(StepData $data): self
    {
        $this->dataPerStep[$data->getStep()] = $data;

        return $this;
    }

    public function all(): array
    {
        return $this->dataPerStep;
    }

    public function getAllSubmitted(): array
    {
        $data = [];

        foreach ($this->all() as $step) {
            foreach ($step->getSubmitted()->all() as $k => $v) {
                $data[$k] = $v;
            }
        }

        return $data;
    }

    public function getAllLabels(): array
    {
        $data = [];

        foreach ($this->all() as $step) {
            foreach ($step->getLabels()->all() as $k => $v) {
                $data[$k] = $v;
            }
        }

        return $data;
    }

    public function getAllFiles(): array
    {
        $data = [];

        foreach ($this->all() as $step) {
            foreach ($step->getFiles()->all() as $k => $v) {
                $data[$k] = $v;
            }
        }

        return $data;
    }
}
