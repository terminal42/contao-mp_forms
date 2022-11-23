<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Widget;

use Codefog\HasteBundle\StringParser;
use Codefog\HasteBundle\UrlParser;
use Contao\BackendTemplate;
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\String\SimpleTokenParser;
use Contao\FrontendTemplate;
use Contao\StringUtil;
use Contao\System;
use Contao\Widget;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Terminal42\MultipageFormsBundle\FormManagerFactory;

class Placeholder extends Widget
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'form_mp_form_placeholder';

    /**
     * The CSS class prefix.
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-placeholder';

    /**
     * @var bool
     */
    protected $blnSubmitInput = false;

    /**
     * Do not validate this form field.
     */
    public function validator($input)
    {
        return $input;
    }

    public function parse($attributes = null)
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### SUMMARY WITH PLACEHOLDERS ###';

            return $template->parse();
        }

        /** @var SimpleTokenParser $simpleTokenParser */
        $simpleTokenParser = System::getContainer()->get('contao.string.simple_token_parser');

        $this->content = $simpleTokenParser->parse((string) $this->html, $this->generateTokens());

        return parent::parse($attributes);
    }

    /**
     * Old generate() method that must be implemented due to abstract declaration.
     *
     * @throws \BadMethodCallException
     */
    public function generate(): void
    {
        throw new \BadMethodCallException('Calling generate() has been deprecated, you must use parse() instead!');
    }

    private function generateTokens(): array
    {
        $tokens = [];
        $fileTokens = [];
        $summaryTokens = [];

        /** @var FormManagerFactory $factory */
        $factory = System::getContainer()->get(FormManagerFactory::class);

        /** @var StringParser $stringParser */
        $stringParser = System::getContainer()->get(StringParser::class);

        /** @var UrlParser $urlParser */
        $urlParser = System::getContainer()->get(UrlParser::class);

        $manager = $factory->forFormId((int) $this->pid);

        $stepsCollection = $manager->getDataOfAllSteps();

        foreach ($stepsCollection->getAllSubmitted() as $k => $v) {
            $stringParser->flatten($v, 'form_'.$k, $tokens);
            $summaryTokens[$k]['value'] = $tokens['form_'.$k];
        }

        foreach ($stepsCollection->getAllLabels() as $k => $v) {
            $stringParser->flatten($v, 'formlabel_'.$k, $tokens);
            $summaryTokens[$k]['label'] = $tokens['formlabel_'.$k];
        }

        foreach ($stepsCollection->getAllFiles() as $k => $v) {
            try {
                $file = new File($v['tmp_name']);
            } catch (FileNotFoundException $e) {
                continue;
            }

            if ($k === $_GET['summary_download']) {
                throw new ResponseException(new BinaryFileResponse($file));
            }

            $fileTokens['download_url'] = $urlParser->addQueryString('summary_download='.$k);
            $fileTokens['extension'] = $file->getExtension();
            $fileTokens['mime'] = $file->getMimeType();
            $fileTokens['size'] = $file->getSize();

            foreach ($fileTokens as $kk => $vv) {
                $stringParser->flatten($vv, 'file_'.$k.'_'.$kk, $tokens);
            }

            // Generate a general HTML output using the download template
            $tpl = new FrontendTemplate('ce_download'); // TODO: make configurable in form field settings?
            $tpl->link = $file->getBasename($file->getExtension());
            $tpl->title = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $file->getBasename($file->getExtension())));
            $tpl->href = $fileTokens['download_url'];
            $tpl->filesize = System::getReadableSize($file->getSize());
            $tpl->mime = $file->getMimeType();
            $tpl->extension = $file->getExtension();

            $stringParser->flatten($tpl->parse(), 'file_'.$k, $tokens);
            $summaryTokens[$k]['value'] = $tokens['file_'.$k];
        }

        // Add a simple summary token that outputs label plus value for everything that was submitted
        $summaryToken = [];

        foreach ($summaryTokens as $k => $v) {
            if (!isset($v['value'])) {
                continue;
            }

            // Also skip Contao internal tokens and the page switch element
            if (\in_array($k, ['REQUEST_TOKEN', 'FORM_SUBMIT', 'mp_form_pageswitch'], true)) {
                continue;
            }

            $summaryToken[] = sprintf('<div data-ff-name="%s" class="label">%s</div>', htmlspecialchars($k), $v['label'] ?? '');
            $summaryToken[] = sprintf('<div data-ff-name="%s" class="value">%s</div>', htmlspecialchars($k), $v['value'] ?? '');
        }

        $tokens['mp_forms_summary'] = implode("\n", $summaryToken);

        // Add a debug token to help answering the question "Which tokens are available?"
        $debugTokens = [];

        foreach ($tokens as $k => $v) {
            $debugTokens[sprintf('##%s##', $k)] = $v;
        }

        $cloner = new VarCloner();
        $dumper = new HtmlDumper();
        $output = fopen('php://memory', 'r+');
        $dumper->dump($cloner->cloneVar($debugTokens), $output);
        $output = stream_get_contents($output, -1, 0);

        $tokens['mp_forms_debug'] = $output;

        return $tokens;
    }
}
