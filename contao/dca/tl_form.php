<?php

declare(strict_types=1);

use Contao\CoreBundle\DataContainer\PaletteManipulator;

PaletteManipulator::create()
    ->addLegend('mp_forms_legend', hide: true)
    ->addField('mp_forms_getParam', 'mp_forms_legend')
    ->addField('mp_forms_sessionRefParam', 'mp_forms_legend')
    ->addField('mp_forms_backFragment', 'mp_forms_legend')
    ->addField('mp_forms_nextFragment', 'mp_forms_legend')
    ->applyToPalette('default', 'tl_form')
;

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_getParam'] = [
    'default' => 'step',
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => 'step', 'notnull' => true],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_sessionRefParam'] = [
    'default' => 'ref',
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => 'ref', 'notnull' => true],
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_backFragment'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_form']['fields']['mp_forms_nextFragment'] = [
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255],
    'sql' => "varchar(255) NOT NULL default ''",
];
