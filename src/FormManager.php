<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle;

use Codefog\HasteBundle\UrlParser;
use Contao\CoreBundle\Exception\RedirectResponseException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;
use Terminal42\MultipageFormsBundle\Step\StepData;
use Terminal42\MultipageFormsBundle\Step\StepDataCollection;
use Terminal42\MultipageFormsBundle\Storage\StorageInterface;

class FormManager
{
    private FormModel $formModel;
    private string $sessionRef;
    private string $storageIdentifier;

    private bool $prepared = false;
    private bool $preparing = false;

    /**
     * @var array<FormFieldModel>
     */
    private array $formFieldModels;

    /**
     * @var array<int, FormFieldModel>
     */
    private array $formFieldsPerStep = [];

    private bool $isValidFormFieldCombination = true;

    public function __construct(
        private int $formId,
        private Request $request,
        private ContaoFramework $contaoFramework,
        private StorageInterface $storage,
        private UrlParser $urlParser,
    ) {
    }

    public function isFirstStep(): bool
    {
        $this->prepare();

        return 0 === $this->getCurrentStep();
    }

    public function getDataOfAllSteps(): StepDataCollection
    {
        $this->prepare();

        return $this->storage->getData($this->storageIdentifier);
    }

    public function getCurrentStep(): int
    {
        $this->prepare();

        return $this->request->query->getInt($this->getGetParam());
    }

    public function getNumberOfSteps(): int
    {
        $this->prepare();

        return \count(array_keys($this->formFieldsPerStep));
    }

    public function isValidFormFieldCombination(): bool
    {
        $this->prepare();

        return $this->isValidFormFieldCombination
            && $this->getNumberOfSteps() > 1;
    }

    /**
     * @return array<FormFieldModel>
     */
    public function getFieldsWithoutPageBreaks(): array
    {
        $this->prepare();

        $formFields = $this->formFieldModels;

        foreach ($formFields as $k => $formField) {
            if ('mp_form_pageswitch' === $formField->type) {
                unset($formFields[$k]);
            }
        }

        return $formFields;
    }

    public function getDataOfCurrentStep(): StepData
    {
        return $this->getDataOfStep($this->getCurrentStep());
    }

    public function getDataOfStep(int $step): StepData
    {
        $this->validateStep($step);

        $stepCollection = $this->storage->getData($this->storageIdentifier);

        return $stepCollection->get($step);
    }

    public function storeStepData(StepData $stepData): self
    {
        $this->prepare();

        $stepCollection = $this->storage->getData($this->storageIdentifier);
        $stepCollection = $stepCollection->set($stepData);
        $this->storage->storeData($this->storageIdentifier, $stepCollection);

        return $this;
    }

    public function getPreviousStep(): int
    {
        $this->prepare();

        $previous = $this->getCurrentStep() - 1;

        if ($previous < 0) {
            $previous = 0;
        }

        return $previous;
    }

    public function getFirstInvalidStep(): int
    {
        $this->prepare();

        $steps = range(0, $this->getNumberOfSteps() - 1);

        foreach ($steps as $step) {
            $data = $this->getDataOfStep($step);

            if ($data->isEmpty()) {
                return $step;
            }
        }

        return $this->getNumberOfSteps();
    }

    public function hasStep(int $step): bool
    {
        $this->prepare();

        return isset($this->formFieldsPerStep[$step]);
    }

    /**
     * @return array<FormFieldModel>
     */
    public function getFieldsForStep(int $step = 0): array
    {
        $this->validateStep($step);

        return $this->formFieldsPerStep[$step];
    }

    public function isLastStep(): bool
    {
        $this->prepare();

        return $this->getCurrentStep() >= $this->getNumberOfSteps() - 1;
    }

    public function getNextStep(): int
    {
        $this->prepare();

        $next = $this->getCurrentStep() + 1;

        if ($next > $this->getNumberOfSteps()) {
            $next = $this->getNumberOfSteps();
        }

        return $next;
    }

    public function getLabelForStep(int $step): string
    {
        $this->validateStep($step);

        foreach ($this->getFieldsForStep($step) as $formField) {
            if ($this->isPageBreak($formField) && '' !== $formField->label) {
                return $formField->label;
            }
        }

        return 'Step '.($step + 1);
    }

    public function isPreparing(): bool
    {
        return $this->preparing;
    }

    public function getGetParamForSessionReference()
    {
        return $this->formModel->mp_forms_sessionRefParam ?: 'ref';
    }

    /**
     * @throws RedirectResponseException
     */
    public function redirectToStep(int $step): void
    {
        $this->validateStep($step);

        throw new RedirectResponseException($this->getUrlForStep($step));
    }

    public function getUploadedFiles(): ParameterBag
    {
        // Contao 5
        if (0 !== \count($_FILES)) {
            return new ParameterBag($_FILES);
        }

        // Contao 4.13
        if (!$this->request->getSession()->isStarted()) {
            return new ParameterBag();
        }

        return new ParameterBag($_SESSION['FILES'] ?? []);
    }

    public function endSession(): self
    {
        // Empty storage
        $this->storage->storeData($this->storageIdentifier, new StepDataCollection());

        // Force a new session reference
        $this->initSessionReference(true);

        return $this;
    }

    public function getUrlForStep(int $step): string
    {
        $requestUri = urldecode($this->request->getUri());

        if (0 === $step) {
            $url = $this->urlParser->removeQueryString([$this->getGetParam()], $requestUri);
        } else {
            $url = $this->urlParser->addQueryString($this->getGetParam().'='.$step, $requestUri);
        }

        $url = $this->urlParser->addQueryString($this->getGetParamForSessionReference().'='.$this->sessionRef, $url);

        if ($step > $this->getCurrentStep()) {
            $fragment = $this->getFragmentForStep($step, 'next');
        } else {
            $fragment = $this->getFragmentForStep($this->getCurrentStep(), 'back');
        }

        if ($fragment) {
            $url .= '#'.$fragment;
        }

        return $url;
    }

    /**
     * @throws \OutOfBoundsException if the step does not exist
     */
    private function validateStep(int $step): void
    {
        $this->prepare();

        if (!$this->hasStep($step)) {
            throw new \OutOfBoundsException(sprintf('Step %d does not exist.', $step));
        }
    }

    private function getGetParam(): string
    {
        return $this->formModel->mp_forms_getParam ?: 'step';
    }

    private function isPageBreak(FormFieldModel $formField): bool
    {
        return 'mp_form_pageswitch' === $formField->type;
    }

    /**
     * Loads the form field models (calling the compileFormFields hook and ignoring itself).
     */
    private function loadFormFieldModels(): void
    {
        $formFieldModels = $this->contaoFramework->getAdapter(FormFieldModel::class)->findPublishedByPid($this->formModel->id);

        if (null === $formFieldModels) {
            $formFieldModels = [];
        } else {
            $formFieldModels = $formFieldModels->getModels();
        }

        // Needed for the hook
        $form = $this->createDummyForm();

        $systemAdapter = $this->contaoFramework->getAdapter(System::class);

        if (isset($GLOBALS['TL_HOOKS']['compileFormFields']) && \is_array($GLOBALS['TL_HOOKS']['compileFormFields'])) {
            foreach ($GLOBALS['TL_HOOKS']['compileFormFields'] as $callback) {
                $objCallback = $systemAdapter->importStatic($callback[0]);
                $formFieldModels = $objCallback->{$callback[1]}($formFieldModels, $this->getFormId(), $form);
            }
        }

        $this->formFieldModels = $formFieldModels;
    }

    private function prepare(): void
    {
        if ($this->preparing || $this->prepared) {
            return;
        }

        $this->preparing = true;

        $formModel = $this->contaoFramework->getAdapter(FormModel::class)->findByPk($this->formId);

        if (null === $formModel) {
            throw new \InvalidArgumentException(sprintf('Could not load form ID "%d".', $this->formId));
        }

        $this->formModel = $formModel;

        $this->loadFormFieldModels();

        if (0 === \count($this->formFieldModels)) {
            $this->isValidFormFieldCombination = false;
            $this->prepared = true;
            $this->preparing = false;

            return;
        }

        $i = 0;

        foreach ($this->formFieldModels as $formField) {
            $this->formFieldsPerStep[$i][] = $formField;

            if ($this->isPageBreak($formField)) {
                // Set the name on the model, otherwise one has to enter it
                // in the back end every time
                $formField->name = $formField->type;

                // Increase counter
                ++$i;
            }

            // If we have a regular submit form field, that's a misconfiguration
            if ('submit' === $formField->type) {
                $this->isValidFormFieldCombination = false;
            }
        }

        // Set current session reference from request or generate a new one
        $this->initSessionReference();

        // Set storage identifier for storage implementations to work with
        $this->initStorageIdentifier();

        $this->prepared = true;
        $this->preparing = false;
    }

    /**
     * Creates a dummy form instance that is needed for the hooks.
     */
    private function createDummyForm(): Form
    {
        $form = new \stdClass();
        $form->form = $this->formModel->id;

        // Set properties to avoid a warning "Undefined property: stdClass::$variable"
        $form->headline = null;
        $form->typePrefix = null;
        $form->cssID = null;

        return new Form($form);
    }

    private function getFormId(): string
    {
        return '' !== $this->formModel->formID ?
            'auto_'.$this->formModel->formID :
            'auto_form_'.$this->formModel->id;
    }

    /**
     * @param string $mode ("next" or "back")
     */
    private function getFragmentForStep(int $step, string $mode): string
    {
        if (!\in_array($mode, ['back', 'next'], true)) {
            throw new \InvalidArgumentException('Mode must be either "back" or "next".');
        }

        $key = sprintf('mp_forms_%sFragment', $mode);

        foreach ($this->getFieldsForStep($step) as $formField) {
            if ($this->isPageBreak($formField) && '' !== $formField->{$key}) {
                return $formField->{$key};
            }
        }

        if ('' !== $this->formModel->{$key}) {
            return $this->formModel->{$key};
        }

        return '';
    }

    private function initSessionReference(bool $forceNew = false): void
    {
        if ($forceNew) {
            $this->sessionRef = bin2hex(random_bytes(16));

            return;
        }

        $this->sessionRef = $this->request->query->get(
            $this->getGetParamForSessionReference(),
            bin2hex(random_bytes(16))
        );
    }

    private function initStorageIdentifier(): void
    {
        $info = [];
        $info[] = $this->formId;
        $info[] = $this->sessionRef;

        // Ensure the identifier changes, when the fields are updated as the settings might change
        foreach ($this->formFieldModels as $fieldModel) {
            $info[] = $fieldModel->tstamp;
        }

        $this->storageIdentifier = sha1(implode(';', $info));
    }
}