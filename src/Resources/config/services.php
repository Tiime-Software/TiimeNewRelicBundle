<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

use Tiime\NewRelicBundle\NewRelic\AdaptiveInteractor;
use Tiime\NewRelicBundle\NewRelic\BlackholeInteractor;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractor;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure()
            ->private()
    ;

    $services->load('Tiime\\NewRelicBundle\\Command\\', '../../Command/*');
    $services->load('Tiime\\NewRelicBundle\\NewRelic\\', '../../NewRelic/*');
    $services->load('Tiime\\NewRelicBundle\\TransactionNamingStrategy\\', '../../TransactionNamingStrategy/*');

    $services->set(AdaptiveInteractor::class)
        ->args([
            service(NewRelicInteractor::class),
            service(BlackholeInteractor::class),
        ])
    ;
};
