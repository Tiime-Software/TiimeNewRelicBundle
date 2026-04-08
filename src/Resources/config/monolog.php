<?php

declare(strict_types=1);

use Monolog\Handler\NewRelicHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private()
    ;

    $services->load('Tiime\\NewRelicBundle\\Logging\\', '../../Logging/*');

    $services->set('tiime.new_relic.monolog_handler', NewRelicHandler::class);
};
