<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\InsertTag;

use Contao\CoreBundle\DependencyInjection\Attribute\AsInsertTag;
use Contao\CoreBundle\InsertTag\InsertTagResult;
use Contao\CoreBundle\InsertTag\ResolvedInsertTag;
use Contao\CoreBundle\InsertTag\Resolver\InsertTagResolverNestedResolvedInterface;
use Terminal42\MultipageFormsBundle\FormManager;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

#[AsInsertTag('mp_forms')]
class MultipageFormsInsertTag implements InsertTagResolverNestedResolvedInterface
{
    public function __construct(private readonly FormManagerFactoryInterface $formManagerFactory)
    {
    }

    public function __invoke(ResolvedInsertTag $insertTag): InsertTagResult
    {
        $formId = $insertTag->getParameters()->get(0);
        $type = $insertTag->getParameters()->get(1);
        $value = $insertTag->getParameters()->get(2) ?? '';

        $manager = $this->formManagerFactory->forFormId((int) $formId);

        return new InsertTagResult(match ($type) {
            'step' => $this->getStepValue($manager, $value),
            'field_value' => $manager->getDataOfAllSteps()->getAllSubmitted()[$value] ?? '',
            default => '',
        });
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
