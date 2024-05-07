<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Step;

class StepData
{
    private function __construct(
        private readonly int $step,
        private ParameterBag $submitted,
        private ParameterBag $labels,
        private FileParameterBag $files,
        private ParameterBag $originalPostData,
    ) {
    }

    /**
     * A step is considered empty when there is no submitted data or files. Original
     * post data or labels are no user submitted data.
     */
    public function isEmpty(): bool
    {
        return $this->submitted->empty() && $this->files->empty();
    }

    public function getStep(): int
    {
        return $this->step;
    }

    public function getSubmitted(): ParameterBag
    {
        return $this->submitted;
    }

    public function getLabels(): ParameterBag
    {
        return $this->labels;
    }

    public function getFiles(): FileParameterBag
    {
        return $this->files;
    }

    public function getOriginalPostData(): ParameterBag
    {
        return $this->originalPostData;
    }

    public function withOriginalPostData(ParameterBag $originalPostData): self
    {
        $clone = clone $this;
        $clone->originalPostData = $originalPostData;

        return $clone;
    }

    public function withLabels(ParameterBag $labels): self
    {
        $clone = clone $this;
        $clone->labels = $labels;

        return $clone;
    }

    public function withSubmitted(ParameterBag $submitted): self
    {
        $clone = clone $this;
        $clone->submitted = $submitted;

        return $clone;
    }

    public function withFiles(FileParameterBag $files): self
    {
        $clone = clone $this;
        $clone->files = $files;

        return $clone;
    }

    public static function create(int $step): self
    {
        return new self(
            $step,
            new ParameterBag(),
            new ParameterBag(),
            new FileParameterBag(),
            new ParameterBag(),
        );
    }
}
