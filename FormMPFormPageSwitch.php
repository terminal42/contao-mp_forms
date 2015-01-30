<?php

/**
 * fineuploader extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */
class FormMPFormPageSwitch extends Widget
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'form_page_switch';


    /**
     * Add specific attributes
     * @param string
     * @param mixed
     */
    public function __set($strKey, $varValue)
    {
        switch ($strKey) {
            case 'required':
            case 'mandatory':
                // Ignore
                break;

            case 'singleSRC':
                $this->arrConfiguration['singleSRC'] = $varValue;
                break;

            case 'imageSubmit':
                $this->arrConfiguration['imageSubmit'] = $varValue ? true : false;
                break;

            case 'name':
                $this->arrAttributes['name'] = $varValue;
                break;

            default:
                parent::__set($strKey, $varValue);
                break;
        }
    }


    /**
     * Validate input and set value
     */
    public function validate()
    {
        return;
    }

    /**
     * Generate the widget and return it as string
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            $objTemplate           = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### PAGE BREAK ###';

            return $objTemplate->parse();
        }

        if ($this->imageSubmit && is_file(TL_ROOT . '/' . $this->singleSRC)) {
            return sprintf('<input type="image" src="%s" id="ctrl_%s" name="%s" class="next submit%s" title="%s" alt="%s"%s%s',
                $this->singleSRC,
                'mpform_submit_' . $this->pid,
                'mpform_submit_' . $this->pid,
                (strlen($this->strClass) ? ' ' . $this->strClass : ''),
                specialchars($this->slabel),
                specialchars($this->slabel),
                $this->getAttributes(),
                $this->strTagEnding);
        }

        return sprintf('<input type="submit" id="ctrl_%s" name="%s" class="next submit%s" value="%s"%s%s',
            'mpform_submit_' . $this->pid,
            'mpform_submit_' . $this->pid,
            (strlen($this->strClass) ? ' ' . $this->strClass : ''),
            specialchars($this->slabel),
            $this->getAttributes(),
            $this->strTagEnding);
    }


    /**
     * Add custom HTML after the widget
     * @param array attributes
     * @return string
     */
    public function parse($arrAttributes = null)
    {
        if (TL_MODE == 'BE') {
            return parent::parse($arrAttributes);
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
