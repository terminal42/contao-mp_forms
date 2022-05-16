<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

use Contao\Form;
use Contao\FormCaptcha;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Input;
use Contao\System;
use Contao\Widget;
use Haste\Util\Url;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;

class MPFormsFormManager
{
    public const SESSION_KEY = 'contao.mp_forms';

    /**
     * @var FormModel
     */
    private $formModel;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var FormFieldModel[]
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
     * @param int $formGeneratorId
     */
    public function __construct($formGeneratorId)
    {
        $this->formModel = FormModel::findByPk($formGeneratorId);
        $this->request = System::getContainer()->get('request_stack')->getCurrentRequest();

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
     * Gets the GET param for the steps.
     *
     * @return string
     */
    public function getGetParam()
    {
        return $this->formModel->mp_forms_getParam ?: 'step';
    }

    /**
     * Gets the GET param for the session reference.
     *
     * @return string
     */
    public function getGetParamForSessionReference()
    {
        return $this->formModel->mp_forms_sessionRefParam ?: 'ref';
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
     * Gets the url fragment for a given step
     *
     * @param int    $step
     * @param string $mode ("next" or "back")
     *
     * @return string
     */
    public function getFragmentForStep($step, $mode)
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

    /**
     * Gets the current step.
     *
     * @return int
     */
    public function getCurrentStep()
    {
        return $this->request->query->getInt($this->getGetParam());
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
     * @return string
     */
    public function getUrlForStep($step)
    {
        if (0 === $step) {
            $url = Url::removeQueryString([$this->getGetParam()]);
        } else {
            $url = Url::addQueryString($this->getGetParam() . '=' . $step);
        }

        $url = Url::addQueryString($this->getGetParamForSessionReference() . '=' . $this->getSessionRef(), $url);

        if ($step > $this->getCurrentStep()) {
            $fragment = $this->getFragmentForStep($step, 'next');
        } else {
            $fragment = $this->getFragmentForStep($this->getCurrentStep(), 'back');
        }

        if ($fragment) {
            $url .= '#' . $fragment;
        }

        return $url;
    }

    public function setPostData(array $postData)
    {
        $this->writeToSession(sprintf('[MPFORMSTORAGE_POSTDATA][%s][%d]',
            $this->getSessionIdentifier(),
            $this->getCurrentStep()
        ), $postData);

        return $this;
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
        // Make sure files are moved to our own tmp directory so they are
        // kept across php processes
        foreach ($files as $k => $file) {
            // If the user marked the form field to upload the file into
            // a certain directory, this check will return false and thus
            // we won't move anything.

            if (is_uploaded_file($file['tmp_name'])) {
                $target = sprintf('%s/mp_forms_%s.%s',
                    sys_get_temp_dir(),
                    basename($file['tmp_name']),
                    $this->guessFileExtension($file)
                );
                move_uploaded_file($file['tmp_name'], $target);
                $files[$k]['tmp_name'] = $target;

                // Compatibility with notification center
                $files[$k]['uploaded'] = true;
            }
        }

        // If the current step is 0, we don't want to check for hasPreviousSession(), as this is false on initial page
        // load (in case there is no previous session of course)
        $checkPreviousSessionForPostData = $this->getCurrentStep() !== 0;

        $this->writeToSession(sprintf('[MPFORMSTORAGE][%s][%d]',
            $this->getSessionIdentifier(),
            $this->getCurrentStep()
        ), [
            'submitted'         => $submitted,
            'labels'            => $labels,
            'files'             => $files,
            'originalPostData'  => $this->readFromSession(sprintf('[MPFORMSTORAGE_POSTDATA][%s][%d]',
                    $this->getSessionIdentifier(),
                    $this->getCurrentStep()
                ), $checkPreviousSessionForPostData) ?? [],
        ]);
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
        return (array) $this->readFromSession(sprintf('[MPFORMSTORAGE][%s][%d]',
                $this->getSessionIdentifier(),
                $step
            ));
    }

    /**
     * Get data of all steps merged into one array.
     *
     * @return array
     */
    public function getDataOfAllSteps()
    {
        $submitted        = [];
        $labels           = [];
        $files            = [];
        $originalPostData = [];

        foreach ((array) $this->readFromSession(sprintf('[MPFORMSTORAGE][%s]', $this->getSessionIdentifier())) as $stepData) {
            $submitted        = array_merge($submitted, (array) $stepData['submitted']);
            $labels           = array_merge($labels, (array) $stepData['labels']);
            $files            = array_merge($files, (array) $stepData['files']);
            $originalPostData = array_merge($files, (array) $stepData['originalPostData']);
        }

        return [
            'submitted'        => $submitted,
            'labels'           => $labels,
            'files'            => $files,
            'originalPostData' => $originalPostData,
        ];
    }

    public function resetData()
    {
        foreach (['MPFORMSTORAGE', 'MPFORMSTORAGE_POSTDATA', 'MPFORMSTORAGE_PSWI'] as $sessionKey) {
            $data = $this->readFromSession(sprintf('[%s]', $sessionKey));

            foreach (array_keys((array) $data) as $sessionIdentifier) {
                if (0 === strncmp($sessionIdentifier, $this->formModel->id, \strlen($this->formModel->id))) {
                    $this->writeToSession(sprintf('[%s][%s]', $sessionKey, $sessionIdentifier), []);
                }
            }
        }
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
     * @param FormFieldModel $formField
     * @param int             $step
     *
     * @return bool
     */
    public function validateField(FormFieldModel $formField, $step)
    {
        $class = $GLOBALS['TL_FFL'][$formField->type];

        if (!class_exists($class)) {
            return true;
        }

        /** @var Widget $widget */
        $widget = new $class($formField->row());
        $widget->required = $formField->mandatory ? true : false;
        $widget->decodeEntities = true; // Always decode entities

        // Needed for the hook
        $form = $this->createDummyForm();

        // HOOK: load form field callback
        if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && is_array($GLOBALS['TL_HOOKS']['loadFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadFormField'] as $callback) {
                $objCallback = System::importStatic($callback[0]);
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

            // Handle regular fields
            if ($this->isStoredInData($widget->name, $step)) {
                $value = $this->fetchFromData($widget->name, $step);

                if ($widget->useRawRequestData) {
                    $this->request->request->set($widget->name, $value);

                    // Special handling for FormPassword (must have already been correct once so we can reuse the submitted value)
                    if ($widget instanceof \Contao\FormPassword) {
                        $this->request->request->set($widget->name . '_confirm', $value);
                    }
                } else {
                    Input::setPost($widget->name, $value);
                }
            } else {
                Input::setPost($widget->name, '');
            }

            // Handle files
            if ($this->isStoredInData($widget->name, $step, 'files')) {
                $_FILES[$widget->name] = $this->fetchFromData($widget->name, $step, 'files');
            }

            $fakeValidation = true;
        }

        $widget->validate();

        // HOOK: validate form field callback
        if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && is_array($GLOBALS['TL_HOOKS']['validateFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback) {

                // Do not call ourselves recursively
                if ('MPForms' === $callback[0]) {
                    continue;
                }

                $objCallback = System::importStatic($callback[0]);
                $widget = $objCallback->{$callback[1]}($widget, $this->getFormId(), $this->formModel->row(), $form);
            }
        }

        // Reset fake validation
        if ($fakeValidation) {
            Input::setPost($formField->name, null);
        }

        // Special hack for upload fields because they delete $_FILES and thus
        // multiple validation calls will fail - sigh
        if ($widget instanceof \uploadable && isset($_SESSION['FILES'][$widget->name])) {
            $_FILES[$widget->name] = $_SESSION['FILES'][$widget->name];
        }

        return !$widget->hasErrors();
    }

    /**
     * Stores if some previous step was invalid into the session.
     */
    public function setPreviousStepsWereInvalid()
    {
        $this->writeToSession(sprintf('[MPFORMSTORAGE_PSWI][%s]',
            $this->getSessionIdentifier()
        ), true);
    }

    /**
     * Checks if some previous step was invalid from the session.
     *
     * @return bool
     */
    public function getPreviousStepsWereInvalid()
    {
        return true === $this->readFromSession(sprintf('[MPFORMSTORAGE_PSWI][%s]',
            $this->getSessionIdentifier()
        ));
    }

    /**
     * Resets the session for the previous step check.
     */
    public function resetPreviousStepsWereInvalid()
    {
        $this->writeToSession(sprintf('[MPFORMSTORAGE_PSWI][%s]',
            $this->getSessionIdentifier()
        ), []);
    }

    /**
     * Check if there is data stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     * @param string   $key
     *
     * @return bool
     */
    public function isStoredInData($fieldName, $step = null, $key = 'submitted')
    {
        $step = null === $step ? $this->getCurrentStep() : $step;

        return isset($this->getDataOfStep($step)[$key])
            && array_key_exists($fieldName, $this->getDataOfStep($step)[$key]);
    }

    /**
     * Retrieve the value stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     * @param string   $key
     *
     * @return mixed
     */
    public function fetchFromData($fieldName, $step = null, $key = 'originalPostData')
    {
        $step = null === $step ? $this->getCurrentStep() : $step;

        return $this->getDataOfStep($step)[$key][$fieldName] ?? null;
    }

    /**
     * Helper to check whether a formfieldmodel is of type page break.
     *
     * @param FormFieldModel $formField
     *
     * @return bool
     */
    public function isPageBreak(FormFieldModel $formField)
    {
        return 'mp_form_pageswitch' === $formField->type;
    }

    /**
     * @return string
     */
    private function getSessionIdentifier()
    {
        return $this->formModel->id . '__' . $this->getSessionRef();
    }

    /**
     * Cannot make this a class property because people use `new MPFormsFormManager()` all over the place.
     * We can only introduce proper handling here once there's a completely new version of this extension
     * using DI.
     *
     * @return string
     */
    private function getSessionRef()
    {
        static $sessionRef;

        if (null !== $sessionRef) {
            return $sessionRef;
        }

        return $sessionRef = $this->request->query->get(
            $this->getGetParamForSessionReference(),
            bin2hex(random_bytes(16))
        );
    }

    /**
     * Checks if a widget was submitted in current step handling some
     * exceptions.
     *
     * @return bool
     */
    private function checkWidgetSubmittedInCurrentStep(Widget $widget)
    {
        // Special handling for captcha field
        if ($widget instanceof FormCaptcha) {
            return isset($_POST[$captcha['captcha_' . $widget->id]]);
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
        foreach ($this->formFieldModels as $formField) {
            $this->formFieldsPerStep[$i][] = $formField;

            if ($this->isPageBreak($formField)) {
                // Set the name on the model, otherwise one has to enter it
                // in the back end every time
                $formField->name = $formField->type;

                // Increase counter
                $i++;
            }

            // If we have a regular submit form field, that's a misconfiguration
            if ('submit' === $formField->type) {
                $this->isValidFormFieldCombination = false;
            }
        }
    }

    /**
     * Loads the form field models (calling the compileFormFields hook and ignoring itself).
     */
    private function loadFormFieldModels()
    {
        $formFieldModels = FormFieldModel::findPublishedByPid($this->formModel->id);

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

                $objCallback = System::importStatic($callback[0]);
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

        // Set properties to avoid a warning "Undefined property: stdClass::$variable"
        $form->headline = null;
        $form->typePrefix = null;
        $form->cssID = null;

        return new Form($form);
    }

    private function guessFileExtension(array $file)
    {
        $extension = 'unknown';

        if (!isset($file['type'])) {
            return $extension;
        }

        foreach ($GLOBALS['TL_MIME'] as $ext => $data) {
            if ($data[0] === $file['type']) {
                $extension = $ext;
                break;

            }
        }

        return $extension;
    }

    private function writeToSession(string $propertyPath, $value): void
    {
        if (null === ($session = $this->getSession())) {
            return;
        }

        $data = $session->get(self::SESSION_KEY, []);

        $pa = (new PropertyAccessorBuilder())->getPropertyAccessor();

        $pa->setValue($data, $propertyPath, $value);

        $session->set(self::SESSION_KEY, $data);
    }

    private function readFromSession(string $propertyPath, bool $checkPrevious = false)
    {
        if (null === ($session = $this->getSession($checkPrevious))) {
            return null;
        }

        $data = $session->get(self::SESSION_KEY, []);

        $pa = (new PropertyAccessorBuilder())->getPropertyAccessor();

        return $pa->getValue($data, $propertyPath);
    }

    private function getSession(bool $checkPrevious = false): ?SessionInterface
    {
        if ($checkPrevious && !$this->request->hasPreviousSession()) {
            return null;
        }

        if (!$this->request->hasSession()) {
            return null;
        }

        return $this->request->getSession();
    }
}
