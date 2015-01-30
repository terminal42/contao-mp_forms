<?php
/**
 * fineuploader extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    'FromMPFormPageSwitch'   => 'system/modules/mp_forms/FromMPFormPageSwitch.php',
    'MPForms'                => 'system/modules/mp_forms/MPForms.php'
));
/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
    'form_page_switch'  => 'system/modules/mp_forms/templates'
));