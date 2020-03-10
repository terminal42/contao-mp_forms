<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

use Contao\Image;
use Contao\Widget;
use Contao\BackendTemplate;
use Contao\File;
use Contao\StringUtil as ContaoStringUtil;
use Contao\System;
use Haste\Util\StringUtil as HasteStringUtil;
use Haste\Util\Url;

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

        $this->content = nl2br(ContaoStringUtil::parseSimpleTokens($this->html, $this->generateTokens()));

        return parent::parse($attributes);
    }

    /**
     * Old generate() method that must be implemented due to abstract declaration.
     *
     * @throws \BadMethodCallException
     */
    public function generate()
    {
        throw new \BadMethodCallException('Calling generate() has been deprecated, you must use parse() instead!');
    }

    private function generateTokens(): array
    {
        $tokens = [];
        $summaryTokens = [];

        $manager = new \MPFormsFormManager($this->pid);
        $data = $manager->getDataOfAllSteps();

        foreach ($data['submitted'] as $k => $v) {
            HasteStringUtil::flatten($v, 'form_'.$k, $tokens);
            $summaryTokens[$k]['value'] = $tokens['form_'.$k];
        }

        foreach ($data['labels'] as $k => $v) {
            HasteStringUtil::flatten($v, 'formlabel_'.$k, $tokens);

            $summaryTokens[$k]['label'] = $tokens['formlabel_'.$k];
        }

        $rootDir = \System::getContainer()->getParameter('kernel.project_dir');

        foreach ($data['files'] as $k => $v) {
            $fileTokens = [];

            $file = new File(ContaoStringUtil::stripRootDir($v['tmp_name']));

            if ($k === $_GET['summary_download']) {
                $file->sendToBrowser($v['name']);
            }

            $fileTokens['download_url'] = Url::addQueryString('summary_download=' .$k);
            $fileTokens['extension'] = $file->extension;
            $fileTokens['mime'] = $file->mime;
            $fileTokens['size'] = $file->filesize;

            foreach ($fileTokens as $kk => $vv) {
                HasteStringUtil::flatten($vv, 'file_'.$k.'_'.$kk, $tokens);
            }

            // Generate a general HTML output using the download template
            $tpl = new \Contao\FrontendTemplate('ce_download'); // TODO: make configurable in form field settings?
            $tpl->link = $file->basename;
            $tpl->title = ContaoStringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $file->basename));
            $tpl->href = $fileTokens['download_url'];
            $tpl->filesize = System::getReadableSize($file->filesize);
            $tpl->icon = Image::getPath($file->icon);
            $tpl->mime = $file->mime;
            $tpl->extension = $file->extension;
            $tpl->path = $file->dirname;

            HasteStringUtil::flatten($tpl->parse(), 'file_'.$k, $tokens);

            $summaryTokens[$k]['value'] = $tokens['file_'.$k];
        }

        // Add a simple summary token that outputs label plus value for everything that was submitted
        $summaryToken = '';
        foreach ($summaryTokens as $k => $v) {
            if (!$v['value']) {
                continue;
            }

            $summaryToken .= sprintf('<div class="label">%s</div>', $v['label']);
            $summaryToken .= sprintf('<div class="value">%s</div>', $v['value']);
        }

        $tokens['mp_forms_summary'] = $summaryToken;

        // Add a debug token to help answering the question "Which tokens are available?"
        $debugTokens = ['You can use the following tokens:'];
        foreach($tokens as $k => $v) {
            $debugTokens[] = sprintf('##%s##: %s', $k, $v);
        }

        $tokens['mp_forms_debug'] = implode("\n", $debugTokens);

        return $tokens;
    }
}
