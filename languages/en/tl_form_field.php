<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2012 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Leo Feyer 2005-2012
 * @author     Leo Feyer <http://www.contao.org>
 * @package    mp_forms
 * @license    LGPL
 * @filesource
 */


/**
 * Form fields
 */
$GLOBALS['TL_LANG']['FFL']['mp_form_pageswitch']		= array('Page break', 'Separates the form fields into different pages/steps.');

/**
 * Fields
 */
$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_afterSubmit']	= array('HTML Code after button', 'You can define HTML code that gets inserted right after the button but before the page break.');
$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_progress']		= array('Progress bar', 'Insert a progress bar displaying the current step in percentage or numbers.');
$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_none']			= array('No progress bar', 'Hide the progress bar.');
$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_percentage']		= array('Show progress in percentage', 'Display the current progress in percentage.');
$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_numbers']		= array('Show progress with numbers', 'Display the current progress in numbers.');
