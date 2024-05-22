<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Codefog\HasteBundle\FileUploadNormalizer;
use Codefog\HasteBundle\UrlParser;
use Terminal42\MultipageFormsBundle\Controller\FrontendModule\StepsController;
use Terminal42\MultipageFormsBundle\EventListener\CompileFormFieldsListener;
use Terminal42\MultipageFormsBundle\EventListener\InsertTagsListener;
use Terminal42\MultipageFormsBundle\EventListener\LoadFormFieldListener;
use Terminal42\MultipageFormsBundle\EventListener\PrepareFomDataListener;
use Terminal42\MultipageFormsBundle\FormManagerFactory;
use Terminal42\MultipageFormsBundle\FormManagerFactoryInterface;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->defaults()->autoconfigure();

    $services
        ->set(FormManagerFactory::class)
        ->args([
            service('contao.framework'),
            service('request_stack'),
            service(UrlParser::class),
        ])
        ->public()
        ->alias(FormManagerFactoryInterface::class, FormManagerFactory::class)
        ->public()
    ;

    $services
        ->set(InsertTagsListener::class)
        ->args([
            service(FormManagerFactoryInterface::class),
        ])
    ;

    $services
        ->set(LoadFormFieldListener::class)
        ->args([
            service(FormManagerFactoryInterface::class),
        ])
    ;

    $services
        ->set(CompileFormFieldsListener::class)
        ->args([
            service(FormManagerFactoryInterface::class),
            service('request_stack'),
        ])
    ;

    $services
        ->set(PrepareFomDataListener::class)
        ->args([
            service(FormManagerFactoryInterface::class),
            service('request_stack'),
            service(FileUploadNormalizer::class),
        ])
    ;

    $services
        ->set(StepsController::class)
        ->args([
            service('contao.framework'),
            service(FormManagerFactoryInterface::class),
        ])
    ;
};
