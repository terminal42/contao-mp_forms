<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

class MPFormsFormManager
{
    /**
     * @var \FormModel
     */
    private $formModel;

    /**
     * @var \FormFieldModel[]
     */
    private $formFieldModels;

    /**
     * Array containing the fields per step
     * @var array
     */
    private $formFieldsPerStep = [];

    /**
     * True if last form field is a page break type
     * @var bool
     */
    private $lastFormFieldIsPageBreak = false;

    /**
     * Create a new form manager
     *
     * @param int $formGeneratorId
     */
    function __construct($formGeneratorId)
    {
        $formModel = \FormModel::findByPk($formGeneratorId);

        $this->formModel = $formModel;
        $this->formFieldModels = \FormFieldModel::findPublishedByPid($formModel->id);

        if (null === $this->formModel || null === $this->formFieldModels) {
            throw new \RuntimeException('Cannot manage a form generator ID that
            does not exist or has no published form fields.');
        }

        $this->splitFormFieldsToSteps();
    }

    /**
     * Checks if the combination is valid.
     *
     * @return bool
     */
    public function isValidFormFieldCombination()
    {
        return $this->lastFormFieldIsPageBreak
            && $this->getNumberOfSteps() > 1;
    }

    /**
     * Gets the GET param.
     *
     * @return string
     */
    public function getGetParam()
    {
        return $this->formModel->mp_forms_getParam ?: 'step';
    }

    /**
     * Get the number of steps of the form
     *
     * @return int number of steps
     */
    public function getNumberOfSteps()
    {
        return count(array_keys($this->formFieldsPerStep));
    }

    /**
     * Check if a given step is available
     *
     * @param int $step
     *
     * @return boolean
     */
    public function hasStep($step = 0)
    {
        return isset($this->formFieldsPerStep[$step]);
    }

    /**
     * Get the fields for a given step.
     *
     * @param int $step
     *
     * @return FormFieldModel[]
     *
     * @throws InvalidArgumentException
     */
    public function getFieldsForStep($step = 0)
    {
        if (!$this->hasStep($step)) {
            throw new InvalidArgumentException('Step "' . $step . '" is not available!');
        }

        return $this->formFieldsPerStep[$step];
    }

    /**
     * Get the fields without the page breaks.
     *
     * @return FormFieldModel[]
     */
    public function getFieldsWithoutPageBreaks()
    {
        $formFields = $this->formFieldModels;

        foreach ($formFields as $k => $formField) {
            if ('mp_form_pageswitch' === $formField->type) {
                unset ($formFields[$k]);
            }
        }

        return $formFields;
    }

    /**
     * Gets the label for a given step.
     *
     * @param int $step
     *
     * @return string
     */
    public function getLabelForStep($step)
    {
        foreach ($this->getFieldsForStep($step) as $formField) {
            if ($this->isPageBreak($formField) && '' !== $formField->label) {

                return $formField->label;
            }
        }

        return 'Step ' . ($step + 1);
    }

    /**
     * Gets the current step.
     *
     * @return int
     */
    public function getCurrentStep()
    {
        return (int) \Input::get($this->getGetParam());
    }

    /**
     * Gets the previous step.
     *
     * @return int
     */
    public function getPreviousStep()
    {
        $previous = $this->getCurrentStep() - 1;

        if ($previous < 0) {
            $previous = 0;
        }

        return $previous;
    }

    /**
     * Gets the next step.
     *
     * @return int
     */
    public function getNextStep()
    {
        $next = $this->getCurrentStep() + 1;

        if ($next > $this->getNumberOfSteps()) {
            $next = $this->getNumberOfSteps();
        }

        return $next;
    }

    /**
     * Check if current step is the first.
     *
     * @return bool
     */
    public function isFirstStep()
    {
        if (0 === $this->getCurrentStep()) {

            return true;
        }

        return false;
    }

    /**
     * Check if current step is the last.
     *
     * @return bool
     */
    public function isLastStep()
    {
        if ($this->getCurrentStep() >= ($this->getNumberOfSteps() - 1)) {

            return true;
        }

        return false;
    }

    /**
     * Generates an url for the step.
     *
     * @param int $step
     *
     * @return mixed
     */
    public function getUrlForStep($step)
    {
        if (0 === $step) {
            $url = \Haste\Util\Url::removeQueryString([$this->getGetParam()]);
        } else {
            $url = \Haste\Util\Url::addQueryString($this->getGetParam()  . '=' . $step);
        }

        return $url;
    }

    /**
     * Store data.
     *
     * @param array $submitted
     * @param array $labels
     * @param array $files
     */
    public function storeData(array $submitted, array $labels, array $files)
    {
        $_SESSION['MPFORMSTORAGE'][$this->formModel->id][$this->getCurrentStep()] = [
            'submitted' => $submitted,
            'labels'    => $labels,
            'files'     => $files
        ];
    }

    /**
     * Get data of current step.
     *
     * @return array
     */
    public function getDataOfCurrentStep()
    {
        return (array) $_SESSION['MPFORMSTORAGE'][$this->formModel->id][$this->getCurrentStep()];
    }

    /**
     * Get data of all steps merged into one array.
     *
     * @return array
     */
    public function getDataOfAllSteps()
    {
        $submitted = [];
        $labels    = [];
        $files     = [];

        foreach ((array) $_SESSION['MPFORMSTORAGE'][$this->formModel->id] as $stepData) {
            $submitted = array_merge($submitted, (array) $stepData['submitted']);
            $labels    = array_merge($labels, (array) $stepData['labels']);
            $files     = array_merge($files, (array) $stepData['files']);
        }

        return [
            'submitted' => $submitted,
            'labels'    => $labels,
            'files'     => $files,
        ];
    }

    /**
     * Reset the data.
     */
    public function resetData()
    {
        unset($_SESSION['MPFORMSTORAGE'][$this->formModel->id]);
    }

    /**
     * Validates all steps, optionally accepting custom from -> to ranges
     * to validate only a subset of steps.
     *
     * @param null $stepFrom
     * @param null $stepTo
     *
     * @return true|int True if all steps valid, otherwise the step that failed
     *                  validation
     */
    public function validateSteps($stepFrom = null, $stepTo = null)
    {
        if (null === $stepFrom) {
            $stepFrom = 0;
        }

        if (null === $stepTo) {
            $stepTo = $this->getNumberOfSteps() - 1;
        }

        $steps = range($stepFrom, $stepTo);
        foreach ($steps as $step) {
            if (false === $this->validateStep($step)) {

                return $step;
            }
        }

        return true;
    }

    /**
     * Validates a step.
     *
     * @param $step
     *
     * @return bool
     */
    public function validateStep($step)
    {
        $formFields = $this->getFieldsForStep($step);

        foreach ($formFields as $formField) {
            if (false === $this->validateField($formField)) {

                return false;
            }
        }

        return true;
    }

    /**
     * Validates a field.
     *
     * @param \FormFieldModel $formField
     *
     * @return bool
     */
    public function validateField(\FormFieldModel $formField)
    {
        $class = $GLOBALS['TL_FFL'][$formField->type];

        if (!class_exists($class)) {
            return true;
        }

        /** @var \Widget $widget */
        $widget = new $class($formField->row());
        $widget->required = $formField->mandatory ? true : false;

        // Needed for the hook
        $form = new \Form($this->formModel);
        $formId = ($form->formID != '') ? 'auto_' . $form->formID : 'auto_form_' . $form->id;

        // HOOK: load form field callback
        if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && is_array($GLOBALS['TL_HOOKS']['loadFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadFormField'] as $callback) {
                $objCallback = \System::importStatic($callback[0]);
                $widget = $objCallback->$callback[1]($widget, $formId, $this->formModel->row(), $form);
            }
        }

        $widget->validate();

        // Special hack for upload fields because they delete $_FILES and thus
        // multiple validation calls will fail - sigh
        if ($widget instanceof \uploadable && isset($_SESSION['FILES'][$widget->name])) {
            $_FILES[$widget->name] = $_SESSION['FILES'][$widget->name];
        }

        // HOOK: validate form field callback
        if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && is_array($GLOBALS['TL_HOOKS']['validateFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback) {

                $objCallback = \System::importStatic($callback[0]);
                $widget = $objCallback->$callback[1]($widget, $formId, $this->formModel->row(), $form);
            }
        }

        return !$widget->hasErrors();
    }

    /**
     * Prepare an array that splits up the fields into steps
     */
    private function splitFormFieldsToSteps()
    {
        $i = 0;
        $lastField = null;
        foreach ($this->formFieldModels as $formField) {
            $this->formFieldsPerStep[$i][] = $formField;

            // Fetch value from session (if one switches back)
            if (isset($this->getDataOfCurrentStep()['submitted'])
                && array_key_exists($formField->name, $this->getDataOfCurrentStep()['submitted'])
            ) {
                $formField->value = $this->getDataOfCurrentStep()['submitted'][$formField->name];
            }

            $lastField = $formField;

            if ($this->isPageBreak($formField)) {
                // Set the name on the model, otherwise one has to enter it
                // in the back end every time
                $formField->name = $formField->type;

                // Increase counter
                $i++;
            }
        }

        // Ensure the very last form field is a pageswitch too
        if ($this->isPageBreak($lastField)) {
            $this->lastFormFieldIsPageBreak = true;
        }
    }

    /**
     * Helper to check whether a formfieldmodel is of type page break.
     *
     * @param FormFieldModel $formField
     *
     * @return bool
     */
    private function isPageBreak(\FormFieldModel $formField)
    {
        return 'mp_form_pageswitch' === $formField->type;
    }
}
