<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Tiime\NewRelicBundle\Listener\RequestListener;
use Tiime\NewRelicBundle\Listener\ResponseListener;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private()
    ;

    $services->set(RequestListener::class);
    $services->set(ResponseListener::class);
};
