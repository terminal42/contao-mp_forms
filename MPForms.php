<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */
class MPForms
{
    /**
     * Adjust form fields to given page.
     *
     * @param \FormFieldModel[] $formFields
     * @param string            $formId
     * @param \Form             $form
     */
    public function compileFormFields($formFields, $formId, \Form $form)
    {
        // Make sure empty form fields arrays are skipped
        if (0 === count($formFields)) {

            return $formFields;
        }

        $manager = new MPFormsFormManager(
            $formFields,
            $form->id,
            $form->mp_forms_getParam
        );

        // Don't try to render multi page form if no valid combination
        if (!$manager->isValidFormFieldCombination()) {

            return $manager->getFieldsWithoutPageBreaks();
        }

        // Validate previous steps data
        // Do not validate if we are on the first step (there's no previous data)
        $stepsToValidate = $manager->isFirstStep() ?
            [] :
            range(0, $manager->getCurrentStep() - 1);

        foreach ($stepsToValidate as $step) {
            foreach ($manager->getFieldsForStep($step) as $formField) {
                if (!$this->validateWidget($formField, $formId, $form)) {
                    $this->redirectToStep($manager->getGetParam(), $step);
                }
            }
        }

        return $manager->getFieldsForStep($manager->getCurrentStep());
    }

    /**
     * Store the submitted data into the session and redirect to the next step
     * unless it's the last.
     *
     * @param array $submitted
     * @param array $labels
     * @param \Form $form
     */
    public function prepareFormData(&$submitted, &$labels, \Form $form)
    {
        $manager = new MPFormsFormManager(
            \FormFieldModel::findPublishedByPid($form->id),
            $form->id,
            $form->mp_forms_getParam
        );

        // Don't do anything if not valid
        if (!$manager->isValidFormFieldCombination()) {

            return;
        }

        // Store data in session
        $manager->storeData($submitted, $labels);

        // Want to go back or continue?
        $direction = 'back' === $submitted['mp_form_pageswitch'] ? 'back' : 'continue';
        $nextStep  = 'back' === $direction ? $manager->getPreviousStep() : $manager->getNextStep();

        // Submit form
        if ($manager->isLastStep() && 'continue' === $direction) {

            $allData = $manager->getDataOfAllSteps();

            // Replace data by reference and then return so the default Contao
            // routine kicks in
            $submitted = $allData['submitted'];
            $labels = $allData['labels'];

            // Clear session
            $manager->resetData();
            return;
        }

        $this->redirectToStep($manager->getGetParam(), $nextStep);
    }

    /**
     * Replace InsertTags.
     *
     * @param string $tag
     *
     * @return int|false
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
            \FormFieldModel::findPublishedByPid($form->id),
            $form->id,
            $form->mp_forms_getParam
        );

        switch ($value) {
            case 'current':
                return (int) $manager->getCurrentStep() + 1;
            case 'total':
                return $manager->getNumberOfSteps();
            case 'percentage':
                return ($manager->getCurrentStep() + 1) / ($manager->getNumberOfSteps()) * 100;
            case 'numbers':
                return ($manager->getCurrentStep() + 1) . ' / ' . ($manager->getNumberOfSteps());
        }
    }


    /**
     * Validate widget.
     *
     * @param string $formField
     * @param string $formId
     * @param \Form  $form
     *
     * @return bool
     */
    private function validateWidget($formField, $formId, \Form $form)
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
     * Redirect to step.
     *
     * @param string $getParam
     * @param int    $step
     */
    private function redirectToStep($getParam, $step)
    {
        if ($step === 0) {
            $url = \Haste\Util\Url::removeQueryString([$getParam]);
        } else {
            $url = \Haste\Util\Url::addQueryString($getParam  . '=' . $step);
        }

        \Controller::redirect($url);
    }
}
