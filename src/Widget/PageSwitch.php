<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Widget;

use Contao\BackendTemplate;
use Contao\System;
use Contao\Widget;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

class PageSwitch extends Widget
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'form_mp_form_pageswitch';

    /**
     * The CSS class prefix.
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-pagebreak';

    /**
     * Submit indicator.
     *
     * @var bool
     */
    protected $blnSubmitInput = true;

    /**
     * Do not validate this form field.
     */
    public function validator($input)
    {
        return $input;
    }

    /**
     * Add custom HTML after the widget.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function parse($attributes = null)
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');
            $template->wildcard = '### PAGE BREAK ###';

            return $template->parse();
        }

        /** @var FormManagerFactoryInterface $factory */
        $factory = System::getContainer()->get(FormManagerFactoryInterface::class);

        $manager = $factory->forFormId((int) $this->pid);

        $this->canGoBack = !$manager->isFirstStep();

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
}
