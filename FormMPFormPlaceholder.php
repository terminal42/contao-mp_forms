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

class FormMPFormPlaceholder extends Widget
{
    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_mp_forms_placeholder';

    /**
     * The CSS class prefix
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-placeholder';

    /**
     * @var boolean
     */
    protected $blnSubmitInput = false;

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

    public function parse($attributes = null)
    {
        if (TL_MODE == 'BE') {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### SUMMARY WITH PLACEHOLDERS ###';

            return $template->parse();
        }

        $this->content = nl2br(\Contao\StringUtil::parseSimpleTokens($this->html, $this->generateTokens()));

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

    private function generateTokens(): array
    {
        $tokens = [];

        $manager = new MPFormsFormManager($this->pid);
        $data = $manager->getDataOfAllSteps();

        // TODO: can we support files here?
        foreach ($data['submitted'] as $k => $v) {
            \Haste\Util\StringUtil::flatten($v, 'form_'.$k, $tokens);
        }

        // Add a debug token to help answering the question "Which tokens are available?"
        $debugTokens = ['You can use the following tokens:'];
        foreach($tokens as $k => $v) {
            $debugTokens[] = sprintf('##%s##: %s', $k, $v);
        }

        $tokens['debug_tokens'] = implode("\n", $debugTokens);

        return $tokens;
    }
}
