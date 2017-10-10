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
     *
     * @var array
     */
    private $formFieldsPerStep = [];

    /**
     * True if the manager can handle this form
     *
     * @var bool
     */
    private $isValidFormFieldCombination = true;

    /**
     * Create a new form manager
     *
     * @param int $formGeneratorId
     */
    function __construct($formGeneratorId)
    {
        $this->formModel = \FormModel::findByPk($formGeneratorId);

        $this->prepareFormFields();
    }

    /**
     * Checks if the combination is valid.
     *
     * @return bool
     */
    public function isValidFormFieldCombination()
    {
        return $this->isValidFormFieldCombination
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
     * Gets the form generator form id.
     *
     * @return string
     */
    public function getFormId()
    {
        return ('' !== $this->formModel->formID) ?
            'auto_' . $this->formModel->formID :
            'auto_form_' . $this->formModel->id;
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
                unset($formFields[$k]);
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
            $url = \Haste\Util\Url::addQueryString($this->getGetParam() . '=' . $step);
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
     * Get data of given step.
     *
     * @param int $step
     *
     * @return array
     */
    public function getDataOfStep($step)
    {
        return (array) $_SESSION['MPFORMSTORAGE'][$this->formModel->id][$step];
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
            if (false === $this->validateField($formField, $step)) {

                return false;
            }
        }

        return true;
    }

    /**
     * Validates a field.
     *
     * @param \FormFieldModel $formField
     * @param int             $step
     *
     * @return bool
     */
    public function validateField(\FormFieldModel $formField, $step)
    {
        $class = $GLOBALS['TL_FFL'][$formField->type];

        if (!class_exists($class)) {
            return true;
        }

        /** @var \Widget $widget */
        $widget = new $class($formField->row());
        $widget->required = $formField->mandatory ? true : false;
        $widget->decodeEntities = true; // Always decode entities

        // Needed for the hook
        $form = $this->createDummyForm();

        // HOOK: load form field callback
        if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && is_array($GLOBALS['TL_HOOKS']['loadFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadFormField'] as $callback) {
                $objCallback = \System::importStatic($callback[0]);
                $widget = $objCallback->{$callback[1]}($widget, $this->getFormId(), $this->formModel->row(), $form);
            }
        }

        // Validation (needs to set POST values because the widget class searches
        // only in POST values :-(
        // This should only happen if value is not currently submitted and if
        // the value is neither submitted in POST nor in the session, we have
        // to default it to an empty string so the widget validates for mandatory
        // fields
        $fakeValidation = false;

        if (!$this->checkWidgetSubmittedInCurrentStep($widget)) {
            if ($this->isStoredInData($widget->name, $step)) {
                $value = $this->fetchFromData($widget->name, $step);
            } else {
                $value = '';
            }

            \Input::setPost($formField->name, $value);
            $fakeValidation = true;
        }

        $widget->validate();

        // Reset fake validation
        if ($fakeValidation) {
            \Input::setPost($formField->name, null);
        }

        // Special hack for upload fields because they delete $_FILES and thus
        // multiple validation calls will fail - sigh
        if ($widget instanceof \uploadable && isset($_SESSION['FILES'][$widget->name])) {
            $_FILES[$widget->name] = $_SESSION['FILES'][$widget->name];
        }

        // HOOK: validate form field callback
        if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && is_array($GLOBALS['TL_HOOKS']['validateFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback) {

                $objCallback = \System::importStatic($callback[0]);
                $widget = $objCallback->{$callback[1]}($widget, $this->getFormId(), $this->formModel->row(), $form);
            }
        }

        return !$widget->hasErrors();
    }

    /**
     * Stores if some previous step was invalid into the session.
     */
    public function setPreviousStepsWereInvalid()
    {
        $_SESSION['MPFORMSTORAGE_PSWI'][$this->formModel->id] = true;
    }

    /**
     * Checks if some previous step was invalid from the session.
     *
     * @return bool
     */
    public function getPreviousStepsWereInvalid()
    {
        return true === $_SESSION['MPFORMSTORAGE_PSWI'][$this->formModel->id];
    }

    /**
     * Resets the session for the previous step check.
     */
    public function resetPreviousStepsWereInvalid()
    {
        unset($_SESSION['MPFORMSTORAGE_PSWI'][$this->formModel->id]);
    }

    /**
     * Check if there is data stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     *
     * @return bool
     */
    public function isStoredInData($fieldName, $step = null)
    {
        $step = null === $step ? $this->getCurrentStep() : $step;

        return isset($this->getDataOfStep($step)['submitted'])
            && array_key_exists($fieldName, $this->getDataOfStep($step)['submitted']);
    }

    /**
     * Retrieve the value stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     *
     * @return mixed
     */
    public function fetchFromData($fieldName, $step = null)
    {
        $step = null === $step ? $this->getCurrentStep() : $step;

        return $this->getDataOfStep($step)['submitted'][$fieldName];
    }

    /**
     * Helper to check whether a formfieldmodel is of type page break.
     *
     * @param FormFieldModel $formField
     *
     * @return bool
     */
    public function isPageBreak(\FormFieldModel $formField)
    {
        return 'mp_form_pageswitch' === $formField->type;
    }

    /**
     * Checks if a widget was submitted in current step handling some
     * exceptions.
     *
     * @return bool
     */
    private function checkWidgetSubmittedInCurrentStep(\Widget $widget)
    {
        // Special handling for captcha field
        if ($widget instanceof \FormCaptcha) {
            $session = \Session::getInstance();
            $captcha = $session->get('captcha_' . $widget->id);

            return isset($_POST[$captcha['key']]);
        }

        return isset($_POST[$widget->name]);
    }

    /**
     * Prepare an array that splits up the fields into steps
     */
    private function prepareFormFields()
    {
        if (null === $this->formModel) {
            $this->isValidFormFieldCombination = false;
            return;
        }

        $this->loadFormFieldModels();

        if (0 === count($this->formFieldModels)) {
            $this->isValidFormFieldCombination = false;
            return;
        }

        $i = 0;
        $lastField = null;
        foreach ($this->formFieldModels as $formField) {
            $this->formFieldsPerStep[$i][] = $formField;

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
        if (!$this->isPageBreak($lastField)) {
            $this->isValidFormFieldCombination = false;
        }
    }

    /**
     * Loads the form field models (calling the compileFormFields hook and ignoring itself).
     */
    private function loadFormFieldModels()
    {
        $formFieldModels = \FormFieldModel::findPublishedByPid($this->formModel->id);

        if (null === $formFieldModels) {
            $formFieldModels = [];
        } else {
            $formFieldModels = $formFieldModels->getModels();
        }

        // Needed for the hook
        $form = $this->createDummyForm();

        if (isset($GLOBALS['TL_HOOKS']['compileFormFields']) && is_array($GLOBALS['TL_HOOKS']['compileFormFields'])) {
            foreach ($GLOBALS['TL_HOOKS']['compileFormFields'] as $k => $callback) {

                // Do not call ourselves recursively
                if ('MPForms' === $callback[0]) {
                    continue;
                }

                $objCallback = \System::importStatic($callback[0]);
                $formFieldModels = $objCallback->{$callback[1]}($formFieldModels, $this->getFormId(), $form);
            }
        }

        $this->formFieldModels = $formFieldModels;
    }

    /**
     * Creates a dummy form instance that is needed for the hooks.
     *
     * @return Form
     */
    private function createDummyForm()
    {
        $form = new stdClass();
        $form->form = $this->formModel->id;
        return new \Form($form);
    }
}
