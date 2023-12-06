<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;
use Terminal42\MultipageFormsBundle\Step\ParameterBag;

#[AsHook('loadFormField')]
class LoadFormFieldListener
{
    public function __construct(private readonly FormManagerFactoryInterface $formManagerFactory)
    {
    }

    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (!\is_string($widget->name)) {
            return $widget;
        }

        $manager = $this->formManagerFactory->forFormId((int) $form->id);

        if (!$manager->isValidFormFieldCombination()) {
            return $widget;
        }

        $postData = new ParameterBag($_POST);
        $stepData = $manager->getDataOfCurrentStep();

        // We only prefill the value if it was not submitted in this step.
        // If you submit a value in step 1, go to step 2, then go back to step 1 and submit a wrong value there, Contao
        // would display an error, but we'd prefill it again with the previous value which would make no sense.
        // We prefill in the following order:
        // 1. Submitted data (= validated submitted widget data)
        // 2. Fall back to potentially previously post data which has not been validated yet (e.g. you filled in the values
        //    on step 2 but then navigated back)
        // 3. The widget default value itself
        if (!$postData->has($widget->name)) {
            $widget->value = $stepData->getSubmitted()->get(
                $widget->name, $stepData->getOriginalPostData()->get($widget->name, $widget->value),
            );
        }

        return $widget;
    }
}
