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
    public function __construct(private FormManagerFactoryInterface $formManagerFactory)
    {
    }

    public function __invoke(Widget $widget, string $formId, array $formData, Form $form): Widget
    {
        if (!\is_string($widget->name)) {
            return $widget;
        }

        $postData = new ParameterBag($_POST);

        $manager = $this->formManagerFactory->forFormId((int) $form->id);
        $stepData = $manager->getDataOfCurrentStep();

        // We only prefill the value if it was not submitted in this step.
        // If you submit a value in step 1, go to step 2, then go back to step 1 and submit a wrong value there, Contao
        // would display an error, but we'd prefill it again with the previous value which would make no sense.
        // Moreover, we prefill with submitted data as priority (= validated submitted widget data) and otherwise fall
        // back to potential previous post data which has not been validated yet (e.g. you filled in the values on step 2
        // but then navigated back)
        if (!$postData->has($widget->name)) {
            $widget->value = $stepData->getSubmitted()->get($widget->name, $stepData->getOriginalPostData()->get($widget->name));
        }

        return $widget;
    }
}
