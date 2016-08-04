<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */
class FormMPFormPageSwitch extends FormSubmit
{
    /**
     * Do not validate this form field
     */
    public function validate()
    {
        return;
    }

    /**
     * Add custom HTML after the widget
     *
     * @param array $attributes
     *
     * @return string
     */
    public function parse($attributes = null)
    {
        if (TL_MODE == 'BE') {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### PAGE BREAK ###';

            return $template->parse();
        }

        $form = \FormModel::findByPk($this->pid);
        $manager = new MPFormsFormManager(
            \FormFieldModel::findPublishedByPid($form->id)
        );
        $getParam = $form->mp_forms_getParam ?: 'step';
        $currentStep = (int) \Input::get($getParam);

        $this->current = $currentStep + 1;
        $this->total = $manager->getNumberOfSteps() + 1;
        $this->percentage = ($currentStep + 1) / ($manager->getNumberOfSteps() + 1) * 100;
        $this->numbers    = ($currentStep + 1) . ' / ' . ($manager->getNumberOfSteps() + 1);

        $buffer = parent::parse($attributes);

        return $buffer . $this->mp_forms_afterSubmit;
    }
}
