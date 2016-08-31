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
ClassLoader::addClasses([
    'FormMPFormPageSwitch'   => 'system/modules/mp_forms/FormMPFormPageSwitch.php',
    'MPForms'                => 'system/modules/mp_forms/MPForms.php',
    'MPFormsFormManager'     => 'system/modules/mp_forms/MPFormsFormManager.php',
    'MPFormsStepsModule'     => 'system/modules/mp_forms/MPFormsStepsModule.php',
]);

/**
 * Register the templates
 */
TemplateLoader::addFiles([
    'form_mp_forms_page_switch' => 'system/modules/mp_forms/templates',
    'mod_mp_forms_steps'        => 'system/modules/mp_forms/templates',
]);
