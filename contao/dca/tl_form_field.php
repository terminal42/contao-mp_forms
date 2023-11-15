<?php

declare(strict_types=1);

use Contao\Controller;

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_pageswitch'] = '{type_legend},type,label,mp_forms_backButton,slabel;{image_legend:hide},imageSubmit;{expert_legend:hide},mp_forms_backFragment,mp_forms_nextFragment,class,accesskey,tabindex;{template_legend:hide},customTpl;{invisible_legend:hide},invisible';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_placeholder'] = '{type_legend},type;{text_legend},html;{mp_forms_download_legend},mp_forms_downloadTemplate,mp_forms_downloadInline;{template_legend:hide},customTpl;{invisible_legend:hide},invisible';

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_backButton'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50 clr', 'maxlength' => 255],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => '', 'notnull' => true],
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_backFragment'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => '', 'notnull' => true],
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_nextFragment'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => '', 'notnull' => true],
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_downloadTemplate'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup('ce_download_', [], 'ce_download'),
    'eval' => ['chosen' => true, 'tl_class' => 'clr w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form_field']['fields']['mp_forms_downloadInline'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'm12 w50'],
    'sql' => "char(1) COLLATE ascii_bin NOT NULL default ''",
];
