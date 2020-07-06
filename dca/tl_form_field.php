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
$GLOBALS['TL_DCA']['tl_form_field']['config']['onsubmit_callback'][] = function($dc) {
    $manager = new \MPFormsFormManager((int) $dc->activeRecord->pid);
    $manager->resetData();
};
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_pageswitch'] = '{type_legend},type,label,mp_forms_backButton,slabel;{image_legend:hide},imageSubmit;{expert_legend:hide},mp_forms_backFragment,mp_forms_nextFragment,class,accesskey,tabindex;{template_legend:hide},customTpl;{invisible_legend:hide},invisible';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_placeholder'] = '{type_legend},type;{text_legend},html;{template_legend:hide},customTpl;{invisible_legend:hide},invisible';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_backButton'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_backButton'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50 clr', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_backFragment'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_backFragment'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_nextFragment'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_form_field']['mp_forms_nextFragment'],
    'exclude'   => true,
    'inputType' => 'text',
    'eval'      => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql'       => "varchar(255) NOT NULL default ''"
];
