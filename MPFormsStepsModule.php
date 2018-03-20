<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

use Contao\Module;
use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Haste\Generator\RowClass;

class MPFormsStepsModule extends Module
{

    /**
     * Template
     * @var string
     */
    protected $strTemplate = 'mod_mp_forms_steps';


    /**
     * Display a wildcard in the back end
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE == 'BE') {
            /** @var BackendTemplate|object $objTemplate */
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['mp_form_steps'][0]) . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }


    /**
     * Generate the module
     */
    protected function compile()
    {
        $navTpl = new FrontendTemplate($this->navigationTpl ?: 'nav_default');
        $navTpl->level = 0;
        $navTpl->items = $this->buildNavigationItems();
        $this->Template->navigation = $navTpl->parse();
    }

    /**
     * Builds the navigation array items.
     *
     * @return array
     */
    private function buildNavigationItems()
    {
        $manager = new MPFormsFormManager($this->form);

        $steps = range(0, $manager->getNumberOfSteps() - 1);
        $items = [];

        // Never validate the very last step
        $firstFailingStep = $manager->validateSteps(0, $manager->getNumberOfSteps() - 2);

        foreach ($steps as $step) {

            // Check if step can be accessed
            $cantBeAccessed = true !== $firstFailingStep && $step > $firstFailingStep;

            // Only active if current step or step cannot be accessed because of
            // previous steps
            $isActive = $step === $manager->getCurrentStep() || $cantBeAccessed;

            $items[] = [
                'isActive' => $isActive,
                'class'    => 'step_' . $step . (($cantBeAccessed) ? ' forbidden' : ''),
                'href'     => $manager->getUrlForStep($step),
                'title'    => $manager->getLabelForStep($step),
                'link'     => $manager->getLabelForStep($step),
                'nofollow' => true
            ];
        }

        RowClass::withKey('class')
            ->addFirstLast()
            ->addEvenOdd()
            ->applyTo($items);

        return $items;
    }
}
