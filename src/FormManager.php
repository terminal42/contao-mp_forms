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
use Terminal42\MultipageFormsBundle\Step\StepData;
use Terminal42\MultipageFormsBundle\Step\StepDataCollection;
use Terminal42\MultipageFormsBundle\Storage\SessionReferenceGenerator\SessionReferenceGeneratorInterface;
use Terminal42\MultipageFormsBundle\Storage\StorageIdentifierGenerator\StorageIdentifierGeneratorInterface;
use Terminal42\MultipageFormsBundle\Storage\StorageInterface;

class FormManager
{
    private FormModel $formModel;

    private string $sessionRef;

    private string $storageIdentifier;

    private bool $prepared = false;

    private bool $preparing = false;

    /**
     * @var array<string|int, FormFieldModel>
     */
    private array $formFieldModels;

    /**
     * @var array<int, array<string|int, FormFieldModel>>
     */
    private array $formFieldsPerStep = [];

    private bool $isValidFormFieldCombination = true;

    public function __construct(
        private readonly int $formId,
        private readonly Request $request,
        private readonly ContaoFramework $contaoFramework,
        private readonly StorageInterface $storage,
        private readonly StorageIdentifierGeneratorInterface $storageIdentifierGenerator,
        private readonly SessionReferenceGeneratorInterface $sessionReferenceGenerator,
        private readonly UrlParser $urlParser,
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
        $this->prepare();

        return $this->getDataOfStep($this->getCurrentStep());
    }

    public function getDataOfStep(int $step): StepData
    {
        $this->prepare();

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
        $this->prepare();
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
        $this->prepare();
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
        $this->prepare();

        return $this->formModel->mp_forms_sessionRefParam ?: 'ref';
    }

    /**
     * @throws RedirectResponseException
     */
    public function redirectToStep(int $step): never
    {
        $this->prepare();
        $this->validateStep($step);

        throw new RedirectResponseException($this->getUrlForStep($step));
    }

    public function endSession(): self
    {
        $this->prepare();

        // Empty storage
        $this->storage->storeData($this->storageIdentifier, new StepDataCollection());

        // Force a new session reference
        $this->initSessionReference(true);

        return $this;
    }

    public function getUrlForStep(int $step): string
    {
        $this->prepare();

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
     * Creates a dummy form instance that is needed for the hooks.
     */
    public static function createDummyForm(int $formId): Form
    {
        return new class($formId) extends Form {
            public function __construct(int $formId)
            {
                // Do not call parent in order to not boot the whole system. Just mock some of it.
                $this->id = $formId;
                $this->headline = null;
                $this->typePrefix = null;
                $this->cssID = null;
                $this->strColumn = 'main';
            }
        };
    }

    /**
     * @return array<FormFieldModel>
     */
    public function getFormFieldModels(): array
    {
        $this->prepare();

        return $this->formFieldModels;
    }

    public function getFormId(): string
    {
        $this->prepare();

        return '' !== $this->formModel->formID ?
            'auto_'.$this->formModel->formID :
            'auto_form_'.$this->formModel->id;
    }

    public function getSessionReference(): string
    {
        $this->prepare();

        return $this->sessionRef;
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

    private function loadFormFieldModels(): void
    {
        $collection = $this->contaoFramework->getAdapter(FormFieldModel::class)->findPublishedByPid($this->formModel->id);
        $formFieldModels = [];

        if (null !== $collection) {
            foreach ($collection as $formFieldModel) {
                // Ignore the name of form fields which do not use a name (see
                // contao/core-bundle #1268)
                if (
                    $formFieldModel->name && isset($GLOBALS['TL_DCA']['tl_form_field']['palettes'][$formFieldModel->type])
                    && preg_match('/[,;]name[,;]/', (string) $GLOBALS['TL_DCA']['tl_form_field']['palettes'][$formFieldModel->type])
                ) {
                    $formFieldModels[$formFieldModel->name] = $formFieldModel;
                } else {
                    $formFieldModels[] = $formFieldModel;
                }
            }
        }

        // Needed for the hook
        $form = self::createDummyForm($this->formId);

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

        $formModel = $this->contaoFramework->getAdapter(FormModel::class)->findById($this->formId);

        if (null === $formModel) {
            throw new \InvalidArgumentException(sprintf('Could not load form ID "%d".', $this->formId));
        }

        $this->formModel = $formModel;
        $this->loadFormFieldModels();

        // Set current session reference from request or generate a new one
        $this->initSessionReference();

        // Set storage identifier for storage implementations to work with
        $this->storageIdentifier = $this->storageIdentifierGenerator->generate($this);

        if (0 === \count($this->formFieldModels)) {
            $this->isValidFormFieldCombination = false;
            $this->prepared = true;
            $this->preparing = false;

            return;
        }

        $i = 0;

        foreach ($this->formFieldModels as $k => $formField) {
            $this->formFieldsPerStep[$i][$k] = $formField;

            if ($this->isPageBreak($formField)) {
                // Set the name on the model, otherwise one has to enter it in the back end every time
                $formField->name = $formField->type;

                // Increase counter
                ++$i;
            }

            // If we have a regular submit form field, that's a misconfiguration
            if ('submit' === $formField->type) {
                $this->isValidFormFieldCombination = false;
            }
        }

        $this->prepared = true;
        $this->preparing = false;
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
            if ($this->isPageBreak($formField) && isset($formField->{$key}) && '' !== $formField->{$key}) {
                return $formField->{$key};
            }
        }

        if (isset($this->formModel->{$key}) && '' !== $this->formModel->{$key}) {
            return $this->formModel->{$key};
        }

        return '';
    }

    private function initSessionReference(bool $forceNew = false): void
    {
        if ($forceNew) {
            $this->sessionRef = $this->sessionReferenceGenerator->generate($this);

            return;
        }

        $this->sessionRef = $this->request->query->get(
            $this->getGetParamForSessionReference(),
            $this->sessionReferenceGenerator->generate($this),
        );
    }
}
