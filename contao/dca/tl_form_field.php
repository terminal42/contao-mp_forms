<?php

declare(strict_types=1);

$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_pageswitch'] = '{type_legend},type,label,mp_forms_backButton,slabel;{image_legend:hide},imageSubmit;{expert_legend:hide},mp_forms_backFragment,mp_forms_nextFragment,class,accesskey,tabindex;{template_legend:hide},customTpl;{invisible_legend:hide},invisible';
$GLOBALS['TL_DCA']['tl_form_field']['palettes']['mp_form_placeholder'] = '{type_legend},type;{text_legend},html;{template_legend:hide},customTpl;{invisible_legend:hide},invisible';

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
