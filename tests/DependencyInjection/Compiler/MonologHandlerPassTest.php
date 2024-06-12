<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Tiime\NewRelicBundle\DependencyInjection\Compiler\MonologHandlerPass;

class MonologHandlerPassTest extends AbstractCompilerPassTestCase
{
    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new MonologHandlerPass());
    }

    public function testProcessChannel(): void
    {
        $this->container->setParameter('tiime.new_relic.monolog', ['level' => 100, 'channels' => ['type' => 'inclusive', 'elements' => ['app', 'foo']]]);
        $this->container->setParameter('tiime.new_relic.application_name', 'app');
        $this->registerService('tiime.new_relic.monolog_handler', \Monolog\Handler\NewRelicHandler::class);
        $this->container->setAlias('tiime.new_relic.logs_handler', 'tiime.new_relic.monolog_handler')->setPublic(false);
        $this->registerService('monolog.logger', \Monolog\Logger::class)->setArgument(0, 'app');
        $this->registerService('monolog.logger.foo', \Monolog\Logger::class)->setArgument(0, 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger', 'pushHandler', [new Reference('tiime.new_relic.logs_handler')]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger.foo', 'pushHandler', [new Reference('tiime.new_relic.logs_handler')]);
    }

    public function testProcessChannelAllChannels(): void
    {
        $this->container->setParameter('tiime.new_relic.monolog', ['level' => 100, 'channels' => null]);
        $this->container->setParameter('tiime.new_relic.application_name', 'app');
        $this->registerService('tiime.new_relic.monolog_handler', \Monolog\Handler\NewRelicHandler::class);
        $this->container->setAlias('tiime.new_relic.logs_handler', 'tiime.new_relic.monolog_handler')->setPublic(false);
        $this->registerService('monolog.logger', \Monolog\Logger::class)->setArgument(0, 'app');
        $this->registerService('monolog.logger.foo', \Monolog\Logger::class)->setArgument(0, 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger', 'pushHandler', [new Reference('tiime.new_relic.logs_handler')]);
        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger.foo', 'pushHandler', [new Reference('tiime.new_relic.logs_handler')]);
    }

    public function testProcessChannelExcludeChannels(): void
    {
        $this->container->setParameter('tiime.new_relic.monolog', ['level' => 100, 'channels' => ['type' => 'exclusive', 'elements' => ['foo']]]);
        $this->container->setParameter('tiime.new_relic.application_name', 'app');
        $this->registerService('tiime.new_relic.monolog_handler', \Monolog\Handler\NewRelicHandler::class);
        $this->container->setAlias('tiime.new_relic.logs_handler', 'tiime.new_relic.monolog_handler')->setPublic(false);
        $this->registerService('monolog.logger', \Monolog\Logger::class)->setArgument(0, 'app');
        $this->registerService('monolog.logger.foo', \Monolog\Logger::class)->setArgument(0, 'foo');

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithMethodCall('monolog.logger', 'pushHandler', [new Reference('tiime.new_relic.logs_handler')]);
    }
}
