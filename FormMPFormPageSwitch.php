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

class FormMPFormPageSwitch extends Widget
{
	public $strTemplate = 'form_widget';

	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			return '### PAGE BREAK; Forward label: ' . $this->mp_forms_fwd . '  ###';
		}

		return sprintf('<input name="%s" type="submit" id="ctrl_%s" class="submit%s" value="%s"%s%s',
			'mpform_submit_' . $this->pid,
			$this->strId,
			(strlen($this->strClass) ? ' ' . $this->strClass : ''),
			specialchars($this->mp_forms_fwd),
			$this->getAttributes(),
			$this->strTagEnding);
	}
}
