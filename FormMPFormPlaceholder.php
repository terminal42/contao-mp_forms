<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

use Contao\CoreBundle\Exception\ResponseException;
use Contao\Image;
use Contao\Widget;
use Contao\BackendTemplate;
use Contao\StringUtil as ContaoStringUtil;
use Contao\System;
use Haste\Util\StringUtil as HasteStringUtil;
use Haste\Util\Url;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Symfony\Component\HttpFoundation\File\File;

class FormMPFormPlaceholder extends Widget
{
    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_mp_form_placeholder';

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

        $this->content = ContaoStringUtil::parseSimpleTokens($this->html, $this->generateTokens());

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

        foreach ($data['files'] as $k => $v) {
            $fileTokens = [];

            try{
                $file = new File($v['tmp_name']);
            } catch (FileNotFoundException $e) {
                continue;
            }

            if ($k === $_GET['summary_download']) {
                throw new ResponseException(new BinaryFileResponse($file));
            }

            $fileTokens['download_url'] = Url::addQueryString('summary_download=' .$k);
            $fileTokens['extension'] = $file->getExtension();
            $fileTokens['mime'] = $file->getMimeType();
            $fileTokens['size'] = $file->getSize();

            foreach ($fileTokens as $kk => $vv) {
                HasteStringUtil::flatten($vv, 'file_'.$k.'_'.$kk, $tokens);
            }

            // Generate a general HTML output using the download template
            $tpl = new \Contao\FrontendTemplate('ce_download'); // TODO: make configurable in form field settings?
            $tpl->link = $file->getBasename($file->getExtension());
            $tpl->title = ContaoStringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $file->getBasename($file->getExtension())));
            $tpl->href = $fileTokens['download_url'];
            $tpl->filesize = System::getReadableSize($file->getSize());
            $tpl->mime = $file->getMimeType();
            $tpl->extension = $file->getExtension();

            HasteStringUtil::flatten($tpl->parse(), 'file_'.$k, $tokens);

            $summaryTokens[$k]['value'] = $tokens['file_'.$k];
        }

        // Add a simple summary token that outputs label plus value for everything that was submitted
        $summaryToken = [];
        foreach ($summaryTokens as $k => $v) {
            if (!$v['value']) {
                continue;
            }

            // Also skip Contao internal tokens and the page switch element
            if (in_array($k, ['REQUEST_TOKEN', 'FORM_SUBMIT', 'mp_form_pageswitch'])) {
                continue;
            }

            $summaryToken[] = sprintf('<div data-ff-name="%s" class="label">%s</div>', htmlspecialchars($k), $v['label'] ?? '');
            $summaryToken[] = sprintf('<div data-ff-name="%s" class="value">%s</div>', htmlspecialchars($k), $v['value'] ?? '');
        }

        $tokens['mp_forms_summary'] = implode("\n", $summaryToken);

        // Add a debug token to help answering the question "Which tokens are available?"
        $debugTokens = [];
        foreach($tokens as $k => $v) {
            $debugTokens[sprintf('##%s##', $k)] = $v;
        }

        $cloner = new VarCloner();
        $dumper = new HtmlDumper();
        $output = fopen('php://memory', 'r+b');
        $dumper->dump($cloner->cloneVar($debugTokens), $output);
        $output = stream_get_contents($output, -1, 0);

        $tokens['mp_forms_debug'] = $output;

        return $tokens;
    }
}
