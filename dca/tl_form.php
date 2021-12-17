<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

/**
 * Table tl_form
 */
$GLOBALS['TL_DCA']['tl_form']['config']['onsubmit_callback'][] = function($dc) {
    $manager = new \MPFormsFormManager((int) $dc->id);
    $manager->resetData();
};

$GLOBALS['TL_DCA']['tl_form']['palettes']['default'] .= ';{mp_forms_legend},mp_forms_getParam,mp_forms_sessionRefParam,mp_forms_backFragment,mp_forms_nextFragment';

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_getParam'] = [
    'exclude'   => true,
    'default'   => 'step',
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default 'step'"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_sessionRefParam'] = [
    'exclude'   => true,
    'default'   => 'step',
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default 'ref'"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_backFragment'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_nextFragment'] = [
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default ''"
];