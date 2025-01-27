<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\EventListener;

use Codefog\HasteBundle\FileUploadNormalizer;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;
use Terminal42\MultipageFormsBundle\Step\FileParameterBag;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;

#[AsHook('prepareFormData')]
class PrepareFomDataListener
{
    public function __construct(
        private readonly FormManagerFactoryInterface $formManagerFactory,
        private readonly RequestStack $requestStack,
        private readonly FileUploadNormalizer $fileUploadNormalizer,
    ) {
    }

    /**
     * @param array<Widget> $fields
     */
    public function __invoke(array &$submitted, array &$labels, array $fields, Form $form, array &$files = []): void
    {
        $manager = $this->formManagerFactory->forFormId((int) $form->id);

        // Don't do anything if not valid
        if (!$manager->isValidFormFieldCombination()) {
            return;
        }

        $submittedBag = new ParameterBag($submitted);
        $labelsBag = new ParameterBag($labels);

        $pageSwitchValue = $submittedBag->get('mp_form_pageswitch', '');

        // Store data in session
        $stepData = $manager->getDataOfCurrentStep();
        $stepData = $stepData->withSubmitted($submittedBag);
        $stepData = $stepData->withLabels($labelsBag);

        $stepData = $stepData->withFiles($this->getUploadedFiles($files));

        $manager->storeStepData($stepData);

        // Submit form
        if ($manager->isLastStep() && 'continue' === $pageSwitchValue) {
            $allData = $manager->getDataOfAllSteps();

            // Replace data by reference and then return so the default Contao routine kicks in
            $submitted = $allData->getAllSubmitted();
            $labels = $allData->getAllLabels();
            $files = $allData->getAllFiles();

            // Remove the page switch value field from the submitted data, so it's not passed
            // on in e-mail notifications and the like. However, in intermediate steps we
            // need it because otherwise it's not possible to have an empty step (e.g. only
            // explanation fields) as the step data would be empty and getFirstInvalidStep()
            // would return the one before the empty step.
            unset($submitted['mp_form_pageswitch']);

            // Add session data for Contao 4.13
            if (version_compare(ContaoCoreBundle::getVersion(), '5.0', '<')) {
                // Override $_SESSION['FORM_DATA'] so it contains the data of previous steps as well
                $_SESSION['FORM_DATA'] = $submitted;
                $_SESSION['FILES'] = $allData->getAllFiles();
            }

            // End session
            $manager->endSession();

            return;
        }

        $manager->redirectToStep($manager->getNextStep());
    }

    private function getUploadedFiles($hook = []): FileParameterBag
    {
        // Contao 5
        if (\is_array($hook) && [] !== $hook) {
            return new FileParameterBag($this->fileUploadNormalizer->normalize($hook));
        }

        // Contao 4.13
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            return new FileParameterBag();
        }

        if (!$request->getSession()->isStarted()) {
            return new FileParameterBag();
        }

        return new FileParameterBag($this->fileUploadNormalizer->normalize($_SESSION['FILES'] ?? []));
    }
}
