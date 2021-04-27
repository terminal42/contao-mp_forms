<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

use Contao\Widget;

class FormMPFormPageSwitch extends Widget
{
    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_mp_form_page_switch';

    /**
     * The CSS class prefix
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-pagebreak';

    /**
     * Submit indicator
     * @var boolean
     */
    protected $blnSubmitInput = true;

    /**
     * Do not validate this form field
     *
     * @param string
     *
     * @return string
     */
    public function validator($input)
    {
        return $input;
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

        $manager = new MPFormsFormManager($this->pid);

        $this->canGoBack = !$manager->isFirstStep();

        return parent::parse($attributes);
    }

    /**
     * Old generate() method that must be implemented due to abstract declaration.
     *
     * @throws \BadMethodCallException
     */
    public function generate()
    {
        throw new BadMethodCallException('Calling generate() has been deprecated, you must use parse() instead!');
    }
}
