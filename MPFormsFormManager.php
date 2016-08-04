<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */
class MPFormsFormManager
{
    /**
     * @var FormFieldModel[]
     */
    private $formFieldModels;

    /**
     * Array containing the fields per step
     * @var array
     */
    private $formFieldsPerStep = array();

    /**
     * Create a new form manager
     *
     * @param   FormFieldModel[] $formFieldModels
     */
    function __construct($formFieldModels)
    {
        $this->formFieldModels = $formFieldModels;
        $this->splitFormFieldsToSteps();
    }

    /**
     * Get the number of steps of the form
     *
     * @return int number of steps
     */
    public function getNumberOfSteps()
    {
        return count(array_keys($this->formFieldsPerStep));
    }

    /**
     * Check if a given step is available
     *
     * @param int $step
     * @return boolean
     */
    public function hasStep($step = 0)
    {
        return isset($this->formFieldsPerStep[$step]);
    }

    /**
     * Get the fields for a given step
     *
     * @param int $step
     * @return FormFieldModel[]
     * @throws InvalidArgumentException
     */
    public function getFieldsForStep($step = 0)
    {
        if (!$this->hasStep($step)) {
            throw new InvalidArgumentException('Step "' . $step . '" is not available!');
        }

        return $this->formFieldsPerStep[$step];
    }

    /**
     * Prepare an array that splits up the fields into steps
     */
    private function splitFormFieldsToSteps()
    {
        $i = 0;
        foreach ($this->formFieldModels as $formField) {
            $this->formFieldsPerStep[$i][] = $formField;

            if ($formField->type === 'mp_form_pageswitch') {
                $i++;
            }
        }
    }
}
