<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */
class MPForms extends Controller
{


    /**
     * Prepare an array that sorts the field ids of a certain form id per page
     * @param int form id
     * @return array
     */
    public static function sortFormFieldsPerPage($intForm)
    {
        $arrSorted = array();
        $i         = 0;
        $db        = Database::getInstance();
        $objFields = $db->prepare("SELECT id,type FROM tl_form_field WHERE pid=? AND invisible!=1 ORDER BY sorting")->execute($intForm);
        while ($objFields->next()) {
            if ($objFields->type == 'mp_form_pageswitch') {
                $arrSorted[$i][] = $objFields->id;
                $i++;
                continue;
            }

            $arrSorted[$i][] = $objFields->id;
        }

        return $arrSorted;
    }


    /**
     * Get the number of steps of a form
     * @param int form id
     * @return int number of steps
     */
    public static function getNumberOfSteps($intForm)
    {
        return count(array_keys(self::sortFormFieldsPerPage($intForm)));
    }


    /**
     * Get the current step of a form
     * @param int form id
     * @return int number of current step
     */
    public static function getCurrentStep($intForm)
    {
        $objForm = Database::getInstance()->prepare('SELECT * FROM tl_form WHERE id=?')->execute($intForm);

        $intStep = Input::getInstance()->get((($objForm->mp_forms_getParam) ? $objForm->mp_forms_getParam : 'step'));
        $intStep = ($intStep) ? $intStep : 1;

        return $intStep;
    }


    /**
     * Get the widgets of a step
     * @param int form id
     * @param int step
     * @return array
     */
    public static function getWidgetsOfStep($intForm, $intStep)
    {
        $arrSorted = self::sortFormFieldsPerPage($intForm);

        return $arrSorted[$intStep];
    }


    /**
     * Make sure the Form class doesn't validate all the widgets but the ones on the current page
     * @param Widget
     * @param string form id
     * @param array form data
     */
    public function loadFormField($objWidget, &$formId, $arrData)
    {
        $intTotalSteps = self::getNumberOfSteps($arrData['id']);

        // no steps included, ignore
        if (!$intTotalSteps) {
            return $objWidget;
        }

        $strStepGetParam  = ($arrData['mp_forms_getParam']) ? $arrData['mp_forms_getParam'] : 'step';
        $blnStepSubmitted = (boolean) $this->Input->post('mpform_submit_' . $arrData['id']) || $this->Input->post('mpform_submit_' . $arrData['id'] . '_x');

        // if no parameter is set but we have steps, then we are on step 1
        $intCurrentStep = ($this->Input->get($strStepGetParam)) ? $this->Input->get($strStepGetParam) : 1;

        // redirect to step 1 if somebody feels like he has to enter step=1000000
        if ($intCurrentStep > $intTotalSteps) {
            $this->redirectToStep($strStepGetParam, 1);
        }

        // -1 because we use arrays but a user doesn't get ?step=0
        $intCurrentStep = $intCurrentStep - 1;

        // determine in what step the current widget is
        $intImInStep = self::findStepOfWidget($objWidget->id, $arrData['id']);

        // handle everything that is form submit related first! DO NOT CHANGE!
        // validate only fields from that page
        if ($blnStepSubmitted && $intImInStep == $intCurrentStep) {
            // validate
            $this->validateWidget($objWidget, $formId, $arrData);

            // but the value we only store if there are no errors
            if ($objWidget->hasErrors()) {
                self::storeWidgetErrors($arrData['id'], $objWidget);
            } else {
                self::storeValueInSession($arrData['id'], $objWidget);
                self::removeWidgetErrors($arrData['id'], $objWidget->id);
            }

            // go to the next page if validated and the following conditions apply:
            // - if it's the last step of the form there's no redirect
            // - only redirect when it's the last widget on that step (otherwise following widgets will not be validated)
            // - only redirect if there are no errors for this step
            if ($intCurrentStep != $intTotalSteps
                && self::isLastWidgetOfStep($intCurrentStep, $objWidget, $arrData['id'])
                && !self::stepHasErrors($arrData['id'], $intCurrentStep)
            ) {
                $this->redirectToStep($strStepGetParam, ($intCurrentStep + 2));
            }

            // submit the form if it's the last step and everything is fine
            // otherwise make sure that the form is not sent so we just modify the $formId
            if ($intCurrentStep == $intTotalSteps
                && self::isLastWidgetOfStep($intCurrentStep, $objWidget, $arrData['id'])
                && !self::stepHasErrors($arrData['id'], $intCurrentStep)
            ) {
                self::getValueFromSession($arrData['id'], $objWidget);
                $formId = ($arrData['formID']) ? 'auto_' . $arrData['formID'] : 'auto_form' . $arrData['id'];
            } else {
                $formId = 'dummy_' . str_replace('dummy_', '', $formId);
            }
        } // not submitted form
        else {
            // first of all we load the value from the session (if there is any)
            self::getValueFromSession($arrData['id'], $objWidget);

            // field is in a previous step
            if ($intImInStep < $intCurrentStep) {
                // set value as post data because this is what the validator is going to check
                $this->Input->setPost($objWidget->name, $objWidget->value);

                // validate the field because we cannot go to e.g. step 3 if step 1 is not correct
                $this->validateWidget($objWidget, $formId, $arrData);

                if ($objWidget->hasErrors()) {
                    self::storeWidgetErrors($arrData['id'], $objWidget);
                }
            }

            // field is in the current step
            if ($intImInStep == $intCurrentStep) {
                // check if a previous step has errors and redirect if there are any
                for ($i = 0; $i < $intCurrentStep; $i++) {
                    if (self::stepHasErrors($arrData['id'], $i)) {
                        $this->redirectToStep($strStepGetParam, $i);
                    }
                }

                // show errors if there are any
                if (self::widgetHasErrors($arrData['id'], $objWidget->id)) {
                    foreach (self::getWidgetErrors($arrData['id'], $objWidget->id) as $strError) {
                        $objWidget->addError($strError);
                    }
                }
            }
        }

        // you can make me a dummy as I don't want to be shown other than in the current step
        // but not for the page switch form field
        if ($intImInStep != $intCurrentStep) {
            $objWidget = $this->createDummyWidget($objWidget);
        }

        return $objWidget;
    }


    /**
     * Find the step of a certain widget id
     * @param int widget id
     * @param int form id
     * @return int step id
     */
    private static function findStepOfWidget($intWidgetId, $intForm)
    {
        $intStepId = 0;
        $arrSorted = self::sortFormFieldsPerPage($intForm);
        foreach ($arrSorted as $step => $arrWidgetIds) {
            if (in_array($intWidgetId, $arrWidgetIds)) {
                $intStepId = $step;
                break;
            }
        }

        return $intStepId;
    }


    /**
     * Check whether a widget is the last on a step (apart from the page switch submit button obviously)
     * @param int step
     * @param Widget
     * @param int form id
     * @return boolean
     */
    private static function isLastWidgetOfStep($intStep, Widget $objWidget, $intForm)
    {
        $arrSorted      = self::sortFormFieldsPerPage($intForm);
        $arrStepWidgets = $arrSorted[$intStep];

        // get SECOND last element (because of the page switch submit button)
        if ($arrStepWidgets[count($arrStepWidgets) - 2] == $objWidget->id) {
            return true;
        }

        return false;
    }


    /**
     * Validate a widget
     * @param Widget
     * @param int form id
     * @param array data array
     */
    private function validateWidget($objWidget, $formId, $arrData)
    {
        $objWidget->validate();

        // HOOK: validate form field callback
        if (isset($GLOBALS['TL_HOOKS']['validateFormField']) && is_array($GLOBALS['TL_HOOKS']['validateFormField'])) {
            foreach ($GLOBALS['TL_HOOKS']['validateFormField'] as $callback) {
                $this->import($callback[0]);
                $objWidget = $this->$callback[0]->$callback[1]($objWidget, $formId, $arrData);
            }
        }
    }


    /**
     * Creates a dummy widget
     * @param Widget original widget
     * @return Widget dummy widget
     */
    private function createDummyWidget(Widget $objWidget)
    {
        $arrDummyData = array
        (
            'id'    => $objWidget->id,
            'name'  => $objWidget->name,
            'value' => $objWidget->id // only store the widget id, the value will get added later
        );

        return new FormHidden($arrDummyData);
    }


    /**
     * Redirects to a certain step while keeping all other GET params
     * @param string url
     * @param string GET param
     * @param string new value
     * @return string
     */
    private function redirectToStep($strGet, $intStep)
    {
        $arrChunks    = trimsplit('[?&=]', $this->Environment->request);
        $arrGETParams = array();

        if (is_array($arrChunks) && !empty($arrChunks)) {
            $strUrl = array_shift($arrChunks);

            for ($i = 0, $count = count($arrChunks); $i < $count; $i = $i + 2) {
                if ($arrChunks[$i] != 'step') {
                    $arrGETParams[$arrChunks[$i]] = $arrChunks[$i + 1];
                }
            }

            if ($intStep > 0) {
                $arrGETParams[$strGet] = $intStep;
            }

            $this->redirect(($strUrl . ((count($arrGETParams)) ? '?' : '') . http_build_query($arrGETParams)));
        }
    }


    /**
     * Get a widget value from the session
     * @param int form id
     * @param Widget
     */
    private static function getValueFromSession($intForm, Widget $objWidget)
    {
        if ($_SESSION['MPFORMSTORAGE']['formId_' . $intForm]['widget_value_' . $objWidget->id]) {
            $objWidget->value = $_SESSION['MPFORMSTORAGE']['formId_' . $intForm]['widget_value_' . $objWidget->id];
        }
    }


    /**
     * Store a widget value into the session
     * @param int form id
     * @param Widget
     */
    private static function storeValueInSession($intForm, Widget $objWidget)
    {
        $_SESSION['MPFORMSTORAGE']['formId_' . $intForm]['widget_value_' . $objWidget->id] = $objWidget->value;
    }


    /**
     * Store errors of a widget
     * @param int form id
     * @param Widget
     */
    private static function storeWidgetErrors($intForm, Widget $objWidget)
    {
        $_SESSION['MPFORMSTORAGE']['formId_' . $intForm]['widget_errors_' . $objWidget->id] = $objWidget->getErrors();
    }


    /**
     * Remove errors of a widget
     * @param int form id
     * @param int widget id
     */
    private static function removeWidgetErrors($intForm, $intWidgetId)
    {
        $_SESSION['MPFORMSTORAGE']['formId_' . $intForm]['widget_errors_' . $intWidgetId] = array();
    }


    /**
     * Get errors of a widget
     * @param int form id
     * @param int widget id
     * @return array
     */
    private static function getWidgetErrors($intForm, $intWidgetId)
    {
        return ($_SESSION['MPFORMSTORAGE']['formId_' . $intForm]['widget_errors_' . $intWidgetId]) ? $_SESSION['MPFORMSTORAGE']['formId_' . $intForm]['widget_errors_' . $intWidgetId] : array();
    }


    /**
     * Checks if a widget has an error
     * @param int form id
     * @param int widget id
     * @return boolean
     */
    private static function widgetHasErrors($intForm, $intWidgetId)
    {
        $arrWidgetErrors = self::getWidgetErrors($intForm, $intWidgetId);

        return (!empty($arrWidgetErrors));
    }


    /**
     * Load errors from the session
     * @param int form id
     * @param int step
     * @return array
     */
    private static function getStepErrors($intForm, $intStep)
    {
        $arrWidgets = self::getWidgetsOfStep($intForm, $intStep);
        $arrErrors  = array();

        foreach ($arrWidgets as $intWidgetId) {
            if (self::widgetHasErrors($intForm, $intWidgetId)) {
                $arrErrors[] = self::getWidgetErrors($intForm, $intWidgetId);
            }
        }

        return $arrErrors;
    }


    /**
     * Check if a step has errors
     * @param int form id
     * @param int step
     * @return boolean
     */
    private static function stepHasErrors($intForm, $intStep)
    {
        $arrStepErrors = self::getStepErrors($intForm, $intStep);
        $blnHasErrors  = false;

        foreach ($arrStepErrors as $arrWidgetErrors) {
            if (!empty($arrWidgetErrors)) {
                $blnHasErrors = true;
            }
        }

        return $blnHasErrors;
    }


    /**
     * Replace InsertTags
     * @param string
     * @return string|false
     */
    public function replaceTags($strTag)
    {
        if (strpos($strTag, 'mp_forms::') === false) {
            return false;
        }

        $arrChunks = explode('::', $strTag);

        $intForm = $arrChunks[1];
        $strMode = $arrChunks[2];

        switch ($strMode) {
            case 'current':
                return self::getCurrentStep($intForm);
            case 'total':
                return self::getNumberOfSteps($intForm);
        }
    }
}