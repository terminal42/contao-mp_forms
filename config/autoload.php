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
 * Register the classes
 */
ClassLoader::addClasses(array
(
    'FormMPFormPageSwitch'   => 'system/modules/mp_forms/FormMPFormPageSwitch.php',
    'MPForms'                => 'system/modules/mp_forms/MPForms.php',
    'MPFormsFormManager'     => 'system/modules/mp_forms/MPFormsFormManager.php',
));
