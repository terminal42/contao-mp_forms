<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */
class MPForms
{
    /**
     * Adjust form fields to given page
     *
     * @param   FormFieldModel[] $formFields
     * @param   string $formId
     * @param   Form $form
     */
    public function compileFormFields($formFields, $formId, $form)
    {
        $manager = new MPFormsFormManager($formFields);

        // Don't do anything if no page break
        if ($manager->getNumberOfSteps() === 1) {
            return $formFields;
        }

        $getParam = $form->mp_forms_getParam ?: 'step';
        $currentStep = (int) \Input::get($getParam);
        $isFirst = $currentStep == 0;

        // validate previous steps data
        // Do not validate if we are on the first step (there's no previous data)
        $stepsToValidate = $isFirst ? array() : range(0, $currentStep - 1);
        foreach ($stepsToValidate as $step) {
                foreach ($manager->getFieldsForStep($step) as $formField) {
                    if (!$this->validateWidget($formField, $formId, $form)) {
                        $this->redirectToStep($getParam, $step);
                    }
                }
            }

        return $manager->getFieldsForStep($currentStep);
    }

    /**
     * Store the submitted data into the session and redirect to the next step
     * unless it's the last
     *
     * @param   array $submitted
     * @param   array $labels
     * @param   Form $form
     */
    public function prepareFormData(&$submitted, &$labels, $form)
    {
        $manager = new MPFormsFormManager(
            \FormFieldModel::findPublishedByPid($form->id)
        );

        // Don't do anything if no page break
        if ($manager->getNumberOfSteps() === 1) {
            return;
        }

        $getParam = $form->mp_forms_getParam ?: 'step';
        $currentStep = (int) \Input::get($getParam);
        $isLast = $currentStep == ($manager->getNumberOfSteps() - 1);

        // Complete $submitted and $labels with previous step data and reset session
        if ($isLast) {
            foreach ((array) $_SESSION['MPFORMSTORAGE'][$form->id] as $stepData) {
                $submitted = array_merge($submitted, $stepData['submitted']);
                $labels = array_merge($labels, $stepData['labels']);
            }

            unset($_SESSION['MPFORMSTORAGE'][$form->id]);
            return;
        }

        // Store data in session and redirect to next step
        $_SESSION['MPFORMSTORAGE'][$form->id][$currentStep] = array(
            'submitted' => $submitted,
            'labels'    => $labels
        );

        $this->redirectToStep($getParam, ++$currentStep);
    }

    /**
     * Replace InsertTags
     *
     * @param   string $tag
     * @return  int|false
     */
    public function replaceTags($tag)
    {
        if (strpos($tag, 'mp_forms::') === false) {
            return false;
        }

        $chunks = explode('::', $tag);
        $formId = $chunks[1];
        $value = $chunks[2];

        $form = \FormModel::findByPk($formId);
        $manager = new MPFormsFormManager(
            \FormFieldModel::findPublishedByPid($form->id)
        );
        $getParam = $form->mp_forms_getParam ?: 'step';
        $currentStep = (int) \Input::get($getParam);

        switch ($value) {
            case 'current':
                return (int) $currentStep + 1;
            case 'total':
                return $manager->getNumberOfSteps() + 1;
            case 'percentage':
                return ($currentStep + 1) / ($manager->getNumberOfSteps() + 1) * 100;
            case 'numbers':
                return ($currentStep + 1) . ' / ' . ($manager->getNumberOfSteps() + 1);
        }
    }

    /**
     * Validate a widget
     *
     * @param   FormFieldModel $formField
     * @param   string $formId
     * @param   Form $form
     * @return  boolean
     */
    private function validateWidget($formField, $formId, $form)
    {
        $class = $GLOBALS['TL_FFL'][$formField->type];

        if (!class_exists($class)) {
            return true;
        }

        $widget = new $class($formField->row());
        $widget->required = $formField->mandatory ? true : false;

        // HOOK: load form field callback
        if (isset($GLOBALS['TL_HOOKS']['loadFormField']) && is_array($GLOBALS['TL_HOOKS']['loadFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['loadFormField'] as $callback) {
                $objCallback = \System::importStatic($callback[0]);
                $widget = $objCallback->$callback[1]($widget, $formId, $form->arrData, $form);
            }
        }

        $widget->validate();

        // HOOK: validate form field callback
        if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && is_array($GLOBALS['TL_HOOKS']['validateFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback) {

                $objCallback = \System::importStatic($callback[0]);
                $widget = $objCallback->$callback[1]($widget, $formId, $form->arrData, $form);
            }
        }

        return !$widget->hasErrors();
    }

    /**
     * Redirect to step
     *
     * @param   string $getParam
     * @param   int $step
     */
    private function redirectToStep($getParam, $step)
    {
        if ($step === 0) {
            $url = \Haste\Util\Url::removeQueryString(array($getParam));
        } else {
            $url = \Haste\Util\Url::addQueryString($getParam  . '=' . $step);
        }

        \Controller::redirect($url);
    }
}