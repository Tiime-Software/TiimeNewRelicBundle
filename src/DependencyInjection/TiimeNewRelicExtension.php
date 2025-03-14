<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Tiime\NewRelicBundle\Listener\CommandListener;
use Tiime\NewRelicBundle\Listener\RequestListener;
use Tiime\NewRelicBundle\Listener\ResponseListener;
use Tiime\NewRelicBundle\NewRelic\AdaptiveInteractor;
use Tiime\NewRelicBundle\NewRelic\BlackholeInteractor;
use Tiime\NewRelicBundle\NewRelic\Config;
use Tiime\NewRelicBundle\NewRelic\LoggingInteractorDecorator;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractor;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Tiime\NewRelicBundle\TransactionNamingStrategy\ControllerNamingStrategy;
use Tiime\NewRelicBundle\TransactionNamingStrategy\RouteNamingStrategy;
use Tiime\NewRelicBundle\TransactionNamingStrategy\TransactionNamingStrategyInterface;

/**
 * This is the class that loads and manages your bundle configuration.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class TiimeNewRelicExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        $container->setAlias(NewRelicInteractorInterface::class, $this->getInteractorServiceId($config))->setPublic(false);
        $container->setAlias(TransactionNamingStrategyInterface::class, $this->getTransactionNamingServiceId($config))->setPublic(false);

        if ($config['logging']) {
            $container->register(LoggingInteractorDecorator::class)
                ->setDecoratedService(NewRelicInteractorInterface::class)
                ->setArguments(
                    [
                        '$interactor' => new Reference(LoggingInteractorDecorator::class.'.inner'),
                        '$logger' => new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
                    ]
                )
                ->setPublic(false)
            ;
        }

        if (empty($config['deployment_names'])) {
            $config['deployment_names'] = array_values(array_filter(explode(';', $config['application_name'] ?? '')));
        }

        $container->getDefinition(Config::class)
            ->setArguments(
                [
                    '$name' => $config['application_name'],
                    '$apiKey' => $config['api_key'],
                    '$licenseKey' => $config['license_key'],
                    '$xmit' => $config['xmit'],
                    '$deploymentNames' => $config['deployment_names'],
                    '$apiHost' => $config['api_host'],
                ]
            );

        if ($config['http']['enabled']) {
            $loader->load('http_listener.xml');
            $container->getDefinition(RequestListener::class)
                ->setArguments(
                    [
                        '$ignoredRoutes' => $config['http']['ignored_routes'],
                        '$ignoredPaths' => $config['http']['ignored_paths'],
                        '$symfonyCache' => $config['http']['using_symfony_cache'],
                    ]
                );

            $container->getDefinition(ResponseListener::class)
                ->setArguments(
                    [
                        '$instrument' => $config['instrument'],
                        '$symfonyCache' => $config['http']['using_symfony_cache'],
                    ]
                );
        }

        if ($config['commands']['enabled']) {
            $loader->load('command_listener.xml');
            $container->getDefinition(CommandListener::class)
                ->setArguments(
                    [
                        '$ignoredCommands' => $config['commands']['ignored_commands'],
                    ]
                );
        }

        if ($config['exceptions']['enabled']) {
            $loader->load('exception_listener.xml');
        }

        if ($config['deprecations']['enabled']) {
            $loader->load('deprecation_listener.xml');
        }

        if ($config['twig']) {
            $loader->load('twig.xml');
        }

        if ($config['enabled'] && $config['monolog']['enabled']) {
            if (!class_exists(\Monolog\Handler\NewRelicHandler::class)) {
                throw new \LogicException('The "symfony/monolog-bundle" package must be installed in order to use "monolog" option.');
            }
            $loader->load('monolog.xml');
            $container->setParameter('tiime.new_relic.monolog', $config['monolog'] ?? []);
            $container->setParameter('tiime.new_relic.application_name', $config['application_name']);
            $container->setAlias('tiime.new_relic.logs_handler', $config['monolog']['service'])->setPublic(false);
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private function getInteractorServiceId(array $config): string
    {
        if (!$config['enabled']) {
            return BlackholeInteractor::class;
        }

        if (!isset($config['interactor'])) {
            // Fallback on AdaptiveInteractor.
            return AdaptiveInteractor::class;
        }

        if ('auto' === $config['interactor']) {
            // Check if the extension is loaded or not
            return \extension_loaded('newrelic') ? NewRelicInteractor::class : BlackholeInteractor::class;
        }

        return $config['interactor'];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function getTransactionNamingServiceId(array $config): string
    {
        switch ($config['http']['transaction_naming']) {
            case 'controller':
                return ControllerNamingStrategy::class;
            case 'route':
                return RouteNamingStrategy::class;
            case 'service':
                if (!isset($config['http']['transaction_naming_service'])) {
                    throw new \LogicException('When using the "service", transaction naming scheme, the "transaction_naming_service" config parameter must be set.');
                }

                return $config['http']['transaction_naming_service'];
            default:
                throw new \InvalidArgumentException(\sprintf('Invalid transaction naming scheme "%s", must be "route", "controller" or "service".', $config['http']['transaction_naming']));
        }
    }
}
