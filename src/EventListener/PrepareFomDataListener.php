<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\EventListener;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;
use Terminal42\MultipageFormsBundle\FormManagerFactory;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;

#[AsHook('prepareFormData')]
class PrepareFomDataListener
{
    public function __construct(private FormManagerFactory $formManagerFactory)
    {
    }

    /**
     * @param array<Widget> $fields
     */
    public function __invoke(array &$submitted, array &$labels, array $fields, Form $form, array &$files = []): void
    {
        // TODO: check minimum version for $files which is currently not passed, see https://github.com/contao/contao/pull/5584

        $manager = $this->formManagerFactory->forFormId((int) $form->id);

        // Don't do anything if not valid
        if (!$manager->isValidFormFieldCombination()) {
            return;
        }

        $submitted = new ParameterBag($submitted);
        $labels = new ParameterBag($labels);

        $pageSwitchValue = $submitted->get('mp_form_pageswitch', '');
        $submitted->remove('mp_form_pageswitch');

        // Store data in session
        $stepData = $manager->getDataOfCurrentStep();
        $stepData = $stepData->withSubmitted($submitted);
        $stepData = $stepData->withLabels($labels);
        $stepData = $stepData->withFiles($manager->getUploadedFiles());

        $manager->storeStepData($stepData);

        // Submit form
        if ($manager->isLastStep() && 'continue' === $pageSwitchValue) {
            $allData = $manager->getDataOfAllSteps();

            // Replace data by reference and then return so the default Contao
            // routine kicks in
            $submitted = $allData->getAllSubmitted();
            $labels = $allData->getAllLabels();
            $files = $allData->getAllFiles();

            // Add session data for Contao 4.13
            if (version_compare(ContaoCoreBundle::getVersion(), '5.0', '<')) {
                // Override $_SESSION['FORM_DATA'] so it contains the data of
                // previous steps as well
                $_SESSION['FORM_DATA'] = $submitted;
                $_SESSION['FILES'] = $allData->getAllFiles();
            }

            // End session
            $manager->endSession();

            return;
        }

        $manager->redirectToStep($manager->getNextStep());
    }
}
