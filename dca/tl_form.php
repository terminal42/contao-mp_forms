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
$GLOBALS['TL_DCA']['tl_form']['palettes']['default'] .= ';{mp_forms_legend},mp_forms_getParam;';

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_getParam'] = array
(
    'label'     => &$GLOBALS['TL_LANG']['tl_form']['mp_forms_getParam'],
    'exclude'   => true,
    'default'   => 'step',
    'inputType' => 'text',
    'sql'       => "varchar(255) NOT NULL default 'step'"
);
