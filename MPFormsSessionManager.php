<?php

/**
 * mp_forms extension for Contao Open Source CMS
 *
 * @copyright  Copyright (c) 2015-2016, terminal42 gmbh
 * @author     terminal42 gmbh <info@terminal42.ch>
 * @license    http://opensource.org/licenses/lgpl-3.0.html LGPL
 * @link       https://github.com/terminal42/contao-mp_forms
 */

use Contao\FormModel;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorBuilder;

class MPFormsSessionManager
{
    public const SESSION_KEY = 'contao.mp_forms';

    /**
     * @var FormModel
     */
    private $formModel;

    /**
     * @var Request
     */
    private $request;

    /**
     * @param int $formGeneratorId
     */
    public function __construct($formGeneratorId)
    {
        $this->formModel = FormModel::findByPk($formGeneratorId);
        $this->request = System::getContainer()->get('request_stack')->getCurrentRequest();
    }

    /**
     * Gets the GET param for the steps.
     *
     * @return string
     */
    public function getGetParam()
    {
        return $this->formModel->mp_forms_getParam ?: 'step';
    }

    /**
     * Gets the GET param for the session reference.
     *
     * @return string
     */
    public function getGetParamForSessionReference()
    {
        return $this->formModel->mp_forms_sessionRefParam ?: 'ref';
    }

    /**
     * Gets the current step.
     *
     * @return int
     */
    public function getCurrentStep()
    {
        return $this->request->query->getInt($this->getGetParam());
    }

    public function setPostData(array $postData): void
    {
        $this->writeToSession(sprintf('[MPFORMSTORAGE_POSTDATA][%s][%d]',
            $this->getSessionIdentifier(),
            $this->getCurrentStep()
        ), $postData);
    }

    /**
     * Store data.
     */
    public function storeData(array $submitted, array $labels, array $files): void
    {
        // Make sure files are moved to our own tmp directory so they are
        // kept across php processes
        foreach ($files as $k => $file) {
            // If the user marked the form field to upload the file into
            // a certain directory, this check will return false and thus
            // we won't move anything.

            if (is_uploaded_file($file['tmp_name'])) {
                $target = sprintf('%s/mp_forms_%s.%s',
                    sys_get_temp_dir(),
                    basename($file['tmp_name']),
                    $this->guessFileExtension($file)
                );
                move_uploaded_file($file['tmp_name'], $target);
                $files[$k]['tmp_name'] = $target;

                // Compatibility with notification center
                $files[$k]['uploaded'] = true;
            }
        }

        // If the current step is 0, we don't want to check for hasPreviousSession(), as this is false on initial page
        // load (in case there is no previous session of course)
        $checkPreviousSessionForPostData = $this->getCurrentStep() !== 0;

        $this->writeToSession(sprintf('[MPFORMSTORAGE][%s][%d]',
            $this->getSessionIdentifier(),
            $this->getCurrentStep()
        ), [
            'submitted'         => $submitted,
            'labels'            => $labels,
            'files'             => $files,
            'originalPostData'  => $this->readFromSession(sprintf('[MPFORMSTORAGE_POSTDATA][%s][%d]',
                    $this->getSessionIdentifier(),
                    $this->getCurrentStep()
                ), $checkPreviousSessionForPostData) ?? [],
        ]);
    }

    /**
     * Get data of given step.
     *
     * @param int $step
     *
     * @return array
     */
    public function getDataOfStep($step)
    {
        return (array) $this->readFromSession(sprintf('[MPFORMSTORAGE][%s][%d]',
                $this->getSessionIdentifier(),
                $step
            ));
    }

    /**
     * Get data of all steps merged into one array.
     */
    public function getDataOfAllSteps(): array
    {
        $submitted        = [];
        $labels           = [];
        $files            = [];
        $originalPostData = [];

        foreach ((array) $this->readFromSession(sprintf('[MPFORMSTORAGE][%s]', $this->getSessionIdentifier())) as $stepData) {
            $submitted        = array_merge($submitted, (array) $stepData['submitted']);
            $labels           = array_merge($labels, (array) $stepData['labels']);
            $files            = array_merge($files, (array) $stepData['files']);
            $originalPostData = array_merge($files, (array) $stepData['originalPostData']);
        }

        return [
            'submitted'        => $submitted,
            'labels'           => $labels,
            'files'            => $files,
            'originalPostData' => $originalPostData,
        ];
    }

    public function resetData()
    {
        foreach (['MPFORMSTORAGE', 'MPFORMSTORAGE_POSTDATA', 'MPFORMSTORAGE_PSWI'] as $sessionKey) {
            $data = $this->readFromSession(sprintf('[%s]', $sessionKey));

            foreach (array_keys((array) $data) as $sessionIdentifier) {
                if (0 === strncmp($sessionIdentifier, $this->formModel->id, \strlen($this->formModel->id))) {
                    $this->writeToSession(sprintf('[%s][%s]', $sessionKey, $sessionIdentifier), []);
                }
            }
        }
    }

    /**
     * Stores if some previous step was invalid into the session.
     */
    public function setPreviousStepsWereInvalid()
    {
        $this->writeToSession(sprintf('[MPFORMSTORAGE_PSWI][%s]',
            $this->getSessionIdentifier()
        ), true);
    }

    /**
     * Checks if some previous step was invalid from the session.
     *
     * @return bool
     */
    public function getPreviousStepsWereInvalid()
    {
        return true === $this->readFromSession(sprintf('[MPFORMSTORAGE_PSWI][%s]',
            $this->getSessionIdentifier()
        ));
    }

    /**
     * Resets the session for the previous step check.
     */
    public function resetPreviousStepsWereInvalid()
    {
        $this->writeToSession(sprintf('[MPFORMSTORAGE_PSWI][%s]',
            $this->getSessionIdentifier()
        ), []);
    }

    /**
     * Check if there is data stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     * @param string   $key
     *
     * @return bool
     */
    public function isStoredInData($fieldName, $step = null, $key = 'submitted')
    {
        $step = null === $step ? $this->getCurrentStep() : $step;

        return isset($this->getDataOfStep($step)[$key])
            && array_key_exists($fieldName, $this->getDataOfStep($step)[$key]);
    }

    /**
     * Retrieve the value stored for a certain field name.
     *
     * @param          $fieldName
     * @param null|int $step Current step if null
     * @param string   $key
     *
     * @return mixed
     */
    public function fetchFromData($fieldName, $step = null, $key = 'originalPostData')
    {
        $step = null === $step ? $this->getCurrentStep() : $step;

        return $this->getDataOfStep($step)[$key][$fieldName] ?? null;
    }

    /**
     * @return string
     */
    private function getSessionIdentifier()
    {
        return $this->formModel->id . '__' . $this->getSessionRef();
    }

    /**
     * Cannot make this a class property because people use `new MPFormsFormManager()` all over the place.
     * We can only introduce proper handling here once there's a completely new version of this extension
     * using DI.
     *
     * @return string
     */
    public function getSessionRef()
    {
        static $sessionRef;

        if (null !== $sessionRef) {
            return $sessionRef;
        }

        return $sessionRef = $this->request->query->get(
            $this->getGetParamForSessionReference(),
            bin2hex(random_bytes(16))
        );
    }

    private function guessFileExtension(array $file)
    {
        $extension = 'unknown';

        if (!isset($file['type'])) {
            return $extension;
        }

        foreach ($GLOBALS['TL_MIME'] as $ext => $data) {
            if ($data[0] === $file['type']) {
                $extension = $ext;
                break;

            }
        }

        return $extension;
    }

    private function writeToSession(string $propertyPath, $value): void
    {
        if (null === ($session = $this->getSession())) {
            return;
        }

        $data = $session->get(self::SESSION_KEY, []);

        $pa = (new PropertyAccessorBuilder())->getPropertyAccessor();

        $pa->setValue($data, $propertyPath, $value);

        $session->set(self::SESSION_KEY, $data);
    }

    private function readFromSession(string $propertyPath, bool $checkPrevious = false)
    {
        if (null === ($session = $this->getSession($checkPrevious))) {
            return null;
        }

        $data = $session->get(self::SESSION_KEY, []);

        $pa = (new PropertyAccessorBuilder())->getPropertyAccessor();

        return $pa->getValue($data, $propertyPath);
    }

    private function getSession(bool $checkPrevious = false): ?SessionInterface
    {
        if ($checkPrevious && !$this->request->hasPreviousSession()) {
            return null;
        }

        if (!$this->request->hasSession()) {
            return null;
        }

        return $this->request->getSession();
    }
}
