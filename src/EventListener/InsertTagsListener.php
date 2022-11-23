<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\EventListener;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Terminal42\MultipageFormsBundle\FormManager;
use Terminal42\MultipageFormsBundle\FormManagerFactory;

#[AsHook('replaceInsertTags')]
class InsertTagsListener
{
    public function __construct(private FormManagerFactory $formManagerFactory)
    {
    }

    public function __invoke(string $tag)
    {
        if (!str_starts_with($tag, 'mp_forms::')) {
            return false;
        }

        $chunks = explode('::', $tag);
        $formId = $chunks[1];
        $type = $chunks[2];
        $value = $chunks[3] ?? '';

        $manager = $this->formManagerFactory->forFormId((int) $formId);

        switch ($type) {
            case 'step':
                return $this->getStepValue($manager, $value);
            case 'field_value':
                return $manager->getDataOfAllSteps()->getAllSubmitted()[$value] ?? '';
        }

        return '';
    }

    private function getStepValue(FormManager $manager, string $value): string
    {
        return (string) match ($value) {
            'current' => $manager->getCurrentStep() + 1,
            'total' => $manager->getNumberOfSteps(),
            'percentage' => ($manager->getCurrentStep() + 1) / $manager->getNumberOfSteps() * 100,
            'numbers' => ($manager->getCurrentStep() + 1).' / '.$manager->getNumberOfSteps(),
            'label' => $manager->getLabelForStep($manager->getCurrentStep()),
            default => '',
        };
    }
}
