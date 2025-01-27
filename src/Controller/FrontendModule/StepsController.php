<?php

declare(strict_types=1);

namespace Terminal42\MultipageFormsBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Terminal42\MultipageFormsBundle\FormManager;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

#[AsFrontendModule('mp_form_steps', template: 'mod_mp_form_steps')]
class StepsController extends AbstractFrontendModuleController
{
    public function __construct(
        private readonly ContaoFramework $contaoFramework,
        private readonly FormManagerFactoryInterface $formManagerFactory,
    ) {
    }

    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $manager = $this->formManagerFactory->forFormId($model->form);

        if (!$manager->isValidFormFieldCombination()) {
            return new Response('', Response::HTTP_NO_CONTENT);
        }

        $navTpl = $this->contaoFramework->createInstance(FrontendTemplate::class, [$model->navigationTpl ?: 'nav_default']);
        $navTpl->level = 0;
        $navTpl->items = $this->buildNavigationItems($manager);
        $template->navigation = $navTpl->parse();

        return $template->getResponse();
    }

    private function buildNavigationItems(FormManager $manager)
    {
        $steps = range(0, $manager->getNumberOfSteps() - 1);
        $firstFailingStep = $manager->getFirstInvalidStep();

        $items = [];

        foreach ($steps as $step) {
            $cssClasses = [];
            $cssClasses[] = 'step_'.$step;

            // Check if step can be accessed
            $canBeAccessed = $step <= $firstFailingStep;

            $isCurrent = $step === $manager->getCurrentStep();

            if ($isCurrent) {
                $cssClasses[] = 'current';
            } else {
                $cssClasses[] = $canBeAccessed ? 'accessible' : 'inaccessible';
            }

            // Link only if it's not the current step and it can be accessed
            $shouldDisplayLink = !$isCurrent && $canBeAccessed;

            $items[] = [
                'isActive' => !$shouldDisplayLink, // isActive causes a <span> instead of <a href="">, so we negate
                'class' => implode(' ', $cssClasses),
                'href' => $manager->getUrlForStep($step),
                'pageTitle' => $manager->getLabelForStep($step),
                'title' => $manager->getLabelForStep($step),
                'link' => $manager->getLabelForStep($step),
                'nofollow' => true,
                'accesskey' => '',
                'target' => '',
                'tabindex' => '',
            ];
        }

        return $items;
    }
}
