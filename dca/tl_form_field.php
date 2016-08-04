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
 * Table tl_form_field
 */
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_pageswitch'] = '{type_legend},type,slabel;{image_legend:hide},imageSubmit;{expert_legend:hide},mp_forms_afterSubmit,class,accesskey,tabindex;{template_legend:hide},customTpl';


$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_afterSubmit'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_afterSubmit'],
    'exclude'   => true,
    'inputType' => 'textarea',
    'eval'      => ['tl_class' => 'clr', 'allowHtml' => true],
    'sql'       => "text NULL"
];
