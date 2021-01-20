<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2021, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

use Contao\Controller;
use Contao\Form;
use Contao\FormFieldModel;
use Contao\FormModel;
use Contao\Input;
use Contao\StringUtil;
use Contao\Widget;

class MPForms
{
    /**
     * Adjust form fields to given page.
     *
     * @param FormFieldModel[] $formFields
     * @param string           $formId
     * @param Form             $form
     */
    public function compileFormFields($formFields, $formId, Form $form)
    {
        // Make sure empty form fields arrays are skipped
        if (0 === count($formFields)) {

            return $formFields;
        }

        $manager = new MPFormsFormManager($form->id);

        // Don't try to render multi page form if no valid combination
        if (!$manager->isValidFormFieldCombination()) {

            return $manager->getFieldsWithoutPageBreaks();
        }

        // Do not let Contao validate anything if user wants to go back
        // but still save data already added to the input fields so it is
        // there when they come back to the current step
        if ('back' === $_POST['mp_form_pageswitch']) {

            $manager->storeData($_POST, [], (array) $_SESSION['FILES']);
            $this->redirectToStep($manager, $manager->getPreviousStep());
        }

        // Validate previous steps data but only if not POST present
        // which means data is submitted and you're moving on to the next page
        if (!$manager->isFirstStep() && !$_POST) {
            $vResult = $manager->validateSteps(0, $manager->getCurrentStep() - 1);
            if (true !== $vResult) {
                $manager->setPreviousStepsWereInvalid();
                $this->redirectToStep($manager, $vResult);
            }
        }

        // If someone wanted to skip the page, fake form submission so fields
        // are validated and show the error message.
        if ($manager->getPreviousStepsWereInvalid()) {
            Input::setPost('FORM_SUBMIT', $manager->getFormId());
            $manager->resetPreviousStepsWereInvalid();
        }

        return $manager->getFieldsForStep($manager->getCurrentStep());
    }

    /**
     * Loads the values from the session and adds it as default value to the
     * widget.
     *
     * @param Widget $widget
     * @param string $formId
     * @param array  $formData
     * @param Form   $form
     *
     * @return Widget
     */
    public function loadValuesFromSession(Widget $widget, $formId, $formData, Form $form)
    {
        $manager = new MPFormsFormManager($form->id);

        if ($manager->isStoredInData($widget->name)) {
            $widget->value = $manager->fetchFromData($widget->name);
        }

        return $widget;
    }

    /**
     * Store the submitted data into the session and redirect to the next step
     * unless it's the last.
     *
     * @param array $submitted
     * @param array $labels
     * @param $fieldsOrForm
     * @param $formOrFields
     */
    public function prepareFormData(&$submitted, &$labels, $fieldsOrForm, $formOrFields)
    {
        // Compat with Contao 4 and 3.5
        $form = $fieldsOrForm instanceof Form ? $fieldsOrForm : $formOrFields;

        $manager = new MPFormsFormManager($form->id);

        // Don't do anything if not valid
        if (!$manager->isValidFormFieldCombination()) {

            return;
        }

        $pageSwitchValue = $submitted['mp_form_pageswitch'];
        unset($submitted['mp_form_pageswitch']);

        // Store data in session
        $manager->storeData($submitted, $labels, (array) $_SESSION['FILES']);

        // Submit form
        if ($manager->isLastStep() && 'continue' === $pageSwitchValue) {

            $allData = $manager->getDataOfAllSteps();

            // Replace data by reference and then return so the default Contao
            // routine kicks in
            $submitted          = $allData['submitted'];
            $labels             = $allData['labels'];
            $_SESSION['FILES']  = $allData['files'];

            // Override $_POST so Contao handles special cases like "email"
            // too if the data was submitted in a previous step
            $_POST = $submitted;

            // Override $_SESSION['FORM_DATA'] so it contains the data of
            // previous steps as well
            $_SESSION['FORM_DATA'] = $submitted;

            // Clear session
            $manager->resetData();
            return;
        } else {
            // Make sure the Contao form data session handling doesn't do
            // anything at all while we're on a multipage form
            $_SESSION['FORM_DATA'] = [];
        }

        $this->redirectToStep($manager, $manager->getNextStep());
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
        $type = $chunks[2];
        $value = $chunks[3] ?? '';

        $form = FormModel::findByPk($formId);
        $manager = new MPFormsFormManager($form->id);

        // BC
        if (\in_array($type, ['current', 'total', 'percentage', 'numbers'], true)) {
            $value = $type;
            $type = 'step';
        }

        switch ($type) {
            case 'step':
                return $this->getStepValue($manager, $value);
            case 'field_value':
                $allData = $manager->getDataOfAllSteps();
                return $allData['submitted'][$value] ?? '';
            case 'field_option_label':
                $fields = FormFieldModel::findByPid($formId);

                if (null === $fields) {
                    return '';
                }

                foreach ($fields->fetchAll() as $field) {
                    if ($value === $field['name']) {
                        $allData = $manager->getDataOfAllSteps();
                        $options = StringUtil::deserialize($field['options']);
                        foreach ((array) $options as $option) {
                            if ($allData['submitted'][$value] === $option['value']) {
                                return $option['label'];
                            }
                        }

                        return '';
                    }
                }
                return '';
        }

        return '';
    }

    private function getStepValue(MPFormsFormManager $manager, $value)
    {
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

        return '';
    }

    /**
     * Redirect to step.
     *
     * @param MPFormsFormManager $manager
     * @param int                $step
     */
    private function redirectToStep(MPFormsFormManager $manager, $step)
    {
        Controller::redirect($manager->getUrlForStep($step));
    }
}
