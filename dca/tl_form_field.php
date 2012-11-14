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
 * @copyright  terminal42 gmbh
 * @author     Yanick Witschi <yanick.witschi@terminal42.ch> 
 * @package    mp_forms
 * @license    LGPL 
 * @filesource
 */


/**
 * Table tl_form_field
 */
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_pageswitch'] = '{type_legend},type,slabel;{image_legend:hide},imageSubmit;{expert_legend:hide},mp_forms_afterSubmit,class,accesskey,tabindex';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_afterSubmit'] = array
(
	'label'                   => &$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_afterSubmit'],
	'exclude'                 => true,
	'inputType'               => 'textarea',
	'eval'                    => array('tl_class'=>'clr', 'allowHtml'=>true)
);