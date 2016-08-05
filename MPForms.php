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

        $manager = new MPFormsFormManager($form->id);

        // Don't try to render multi page form if no valid combination
        if (!$manager->isValidFormFieldCombination()) {

            return $manager->getFieldsWithoutPageBreaks();
        }

        // Validate previous steps data
        if (!$manager->isFirstStep()) {
            $vResult = $manager->validateSteps(0, $manager->getCurrentStep() - 1);
            if (true !== $vResult) {
                $this->redirectToStep($manager, $vResult);
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
        $manager = new MPFormsFormManager($form->id);

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

        $this->redirectToStep($manager, $nextStep);
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
        $manager = new MPFormsFormManager($form->id);

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
     * Redirect to step.
     *
     * @param MPFormsFormManager $manager
     * @param int                $step
     */
    private function redirectToStep(MPFormsFormManager $manager, $step)
    {
        \Controller::redirect($manager->getUrlForStep($step));
    }
}
