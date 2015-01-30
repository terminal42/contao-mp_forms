<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015, terminal42 gmbh
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
     * @param   array $arrAttributes
     * @return  string
     */
    public function parse($arrAttributes = null)
    {
        if (TL_MODE == 'BE') {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### PAGE BREAK ###';

            return $template->parse();
        }

        // pass the progress in percentage and numbers to the template
        $currentStep = MPForms::getCurrentStep($this->pid);;
        $totalSteps       = MPForms::getNumberOfSteps($this->pid);
        $this->percentage = $currentStep / $totalSteps * 100;
        $this->numbers    = $currentStep . ' / ' . $totalSteps;

        $strBuffer = parent::parse($arrAttributes);

        return $strBuffer . $this->mp_forms_afterSubmit;
    }
}
