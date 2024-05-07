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
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

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
        $summaryTokens = [];

        /** @var FormManagerFactoryInterface $factory */
        $factory = System::getContainer()->get(FormManagerFactoryInterface::class);

        /** @var StringParser $stringParser */
        $stringParser = System::getContainer()->get(StringParser::class);

        $manager = $factory->forFormId((int) $this->pid);

        $stepsCollection = $manager->getDataOfAllSteps();

        foreach ($stepsCollection->getAllSubmitted() as $formFieldName => $formFieldValue) {
            $stringParser->flatten($formFieldValue, 'form_'.$formFieldName, $tokens);
            $summaryTokens[$formFieldName]['value'] = $tokens['form_'.$formFieldName];
        }

        foreach ($stepsCollection->getAllLabels() as $formFieldName => $formFieldValue) {
            $stringParser->flatten($formFieldValue, 'formlabel_'.$formFieldName, $tokens);
            $summaryTokens[$formFieldName]['label'] = $tokens['formlabel_'.$formFieldName];
        }

        foreach ($stepsCollection->getAllFiles() as $formFieldName => $normalizedFiles) {
            $html = [];

            foreach ($normalizedFiles as $k => $normalizedFile) {
                try {
                    $file = new File($normalizedFile['tmp_name']);
                } catch (FileNotFoundException $e) {
                    return [];
                }

                // Generate the tokens for the index (file 0, 1, 2, ...) and store the HTML per
                // download for later
                $tokens = array_merge($tokens, $this->generateFileTokens($file, 'file_'.$formFieldName.'_'.$k));
                $html[] = $this->generateFileDownloadHtml($file, $this->generateAndHandleDownloadUrl($file, 'file_'.$formFieldName.'_'.$k));

                // If we are at key 0 we also generate one non-indexed token for BC reasons and
                // easier usage for single upload fields.
                if (0 === $k) {
                    $tokens = array_merge($tokens, $this->generateFileTokens($file, 'file_'.$formFieldName));
                }
            }

            // Generate an HTML token (can contain multiple downloads) and add that as the
            // default value for the "file_<formfield>" token and our summary for later
            $htmlToken = implode(' ', $html);
            $tokens['file_'.$formFieldName] = $htmlToken;
            $summaryTokens[$formFieldName]['value'] = $htmlToken;
        }

        // Add a simple summary token that outputs label plus value for everything that
        // was submitted
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
            $summaryToken[] = sprintf('<div data-ff-name="%s" class="value">%s</div>', htmlspecialchars($k), $v['value']);
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

    private function generateFileTokens(File $file, string $tokenKey): array
    {
        $fileTokens = [];
        $fileTokens[$tokenKey.'_download_url'] = $this->generateAndHandleDownloadUrl($file, $tokenKey);
        $fileTokens[$tokenKey.'_extension'] = $file->getExtension();
        $fileTokens[$tokenKey.'_mime'] = $file->getMimeType();
        $fileTokens[$tokenKey.'_size'] = $file->getSize();

        return $fileTokens;
    }

    private function generateAndHandleDownloadUrl(File $file, string $key): string
    {
        if (isset($_GET['summary_download']) && $key === $_GET['summary_download']) {
            $binaryFileResponse = new BinaryFileResponse($file);
            $binaryFileResponse->setContentDisposition(
                $this->mp_forms_downloadInline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                $file->getBasename(),
            );

            throw new ResponseException($binaryFileResponse);
        }

        $urlParser = System::getContainer()->get(UrlParser::class);

        return $urlParser->addQueryString('summary_download='.$key);
    }

    private function generateFileDownloadHtml(File $file, string $downloadUrl): string
    {
        // Generate a general HTML output using the download template
        $tpl = new FrontendTemplate(empty($this->mp_forms_downloadTemplate) ? 'ce_download' : $this->mp_forms_downloadTemplate);
        $tpl->link = $file->getBasename($file->getExtension());
        $tpl->title = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['download'], $file->getBasename($file->getExtension())));
        $tpl->href = $downloadUrl;
        $tpl->filesize = System::getReadableSize($file->getSize());
        $tpl->mime = $file->getMimeType();
        $tpl->extension = $file->getExtension();

        return $tpl->parse();
    }
}
