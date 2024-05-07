<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\FormFieldModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;

#[AsHook('compileFormFields')]
class CompileFormFieldsListener
{
    public function __construct(
        private readonly FormManagerFactoryInterface $formManagerFactory,
        private readonly RequestStack $requestStack,
    ) {
    }

    /**
     * @param array<FormFieldModel> $formFields
     *
     * @return array<FormFieldModel>
     */
    public function __invoke(array $formFields, string $formId, Form $form): array
    {
        if (0 === \count($formFields)) {
            return $formFields;
        }

        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return $formFields;
        }

        $manager = $this->formManagerFactory->forFormId((int) $form->id);

        // If the manager is currently being prepared (recursive compileFormFields hook
        // call), we abort
        if ($manager->isPreparing()) {
            return $formFields;
        }

        // Don't try to render multi page form if no valid combination
        if (!$manager->isValidFormFieldCombination()) {
            return $manager->getFieldsWithoutPageBreaks();
        }

        // Validate whether previous form data was submitted if we're not on the first
        // step. This has to be done no matter if we're in a POST request right now or
        // not as otherwise you can submit a POST request without any previous step data
        // (e.g. by deleting the session cookie manually)
        if (!$manager->isFirstStep()) {
            $firstInvalidStep = $manager->getFirstInvalidStep();

            if ($firstInvalidStep < $manager->getCurrentStep()) {
                $manager->redirectToStep($firstInvalidStep);
            }
        }

        $stepData = $manager->getDataOfCurrentStep();

        // If there is form data submitted in this step, store the original values here no
        // matter if we're going back or if we continue. Important: We do not store $_FILES
        // here! The problem with storing $_FILES across requests is that we would need to move
        // it from its tmp_name as PHP deletes files automatically after the request has
        // finished. We could indeed move them here but if we did at this stage the form fields
        // themselves would later not be able to move them to their own desired place. So we
        // cannot store any file information at this stage.
        if ($_POST) {
            $stepData = $stepData->withOriginalPostData(new ParameterBag($_POST));
            $manager->storeStepData($stepData);
        }

        // Redirect back if asked for it
        if ('back' === $request->request->get('mp_form_pageswitch')) {
            $manager->redirectToStep($manager->getPreviousStep());
        }

        return $manager->getFieldsForStep($manager->getCurrentStep());
    }
}
