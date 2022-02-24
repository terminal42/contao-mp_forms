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
 * Table tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['palettes']['mp_form_steps'] = '
{title_legend},name,headline,type;
{config_legend},form,navigationTpl;
{template_legend:hide},customTpl;
{protected_legend:hide},protected;
{expert_legend:hide},guests,cssID,space';
