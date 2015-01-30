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
 * Form fields
 */
$GLOBALS['TL_FFL']['mp_form_pageswitch']	= 'FormMPFormPageSwitch';

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][]	= array('MPForms', 'replaceTags');
$GLOBALS['TL_HOOKS']['loadFormField'][]		= array('MPForms', 'loadFormField');
