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

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'form_page_switch';


	/**
	 * Add specific attributes
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			case 'required':
			case 'mandatory':
				// Ignore
				break;

			case 'singleSRC':
				$this->arrConfiguration['singleSRC'] = $varValue;
				break;

			case 'imageSubmit':
				$this->arrConfiguration['imageSubmit'] = $varValue ? true : false;
				break;

			case 'name':
				$this->arrAttributes['name'] = $varValue;
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Validate input and set value
	 */
	public function validate()
	{
		return;
	}

	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new BackendTemplate('be_wildcard');
			$objTemplate->wildcard = '### PAGE BREAK ###';
			return $objTemplate->parse();
		}

		if ($this->imageSubmit && is_file(TL_ROOT . '/' . $this->singleSRC))
		{
			return sprintf('<input type="image" src="%s" id="ctrl_%s" name="%s" class="next submit%s" title="%s" alt="%s"%s%s',
				$this->singleSRC,
				'mpform_submit_' . $this->pid,
				'mpform_submit_' . $this->pid,
				(strlen($this->strClass) ? ' ' . $this->strClass : ''),
				specialchars($this->slabel),
				specialchars($this->slabel),
				$this->getAttributes(),
				$this->strTagEnding);
		}

		return sprintf('<input type="submit" id="ctrl_%s" name="%s" class="next submit%s" value="%s"%s%s',
			'mpform_submit_' . $this->pid,
			'mpform_submit_' . $this->pid,
			(strlen($this->strClass) ? ' ' . $this->strClass : ''),
			specialchars($this->slabel),
			$this->getAttributes(),
			$this->strTagEnding);
	}


	/**
	 * Add custom HTML after the widget
	 * @param array attributes
	 * @return string
	 */
	public function parse($arrAttributes = null)
	{
		if (TL_MODE == 'BE')
		{
			return parent::parse($arrAttributes);
		}
        
        // pass the progress in percentage and numbers to the template
		$currentStep = MPForms::getCurrentStep($this->pid);;
		$totalSteps = MPForms::getNumberOfSteps($this->pid);
		$this->percentage = $currentStep / $totalSteps * 100;
		$this->numbers = $currentStep . ' / ' . $totalSteps;
        
		$strBuffer = parent::parse($arrAttributes);
		return $strBuffer . $this->mp_forms_afterSubmit;
	}
}
