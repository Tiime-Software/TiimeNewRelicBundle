<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Matthias\SymfonyDependencyInjectionTest\PhpUnit\ContainerHasParameterConstraint;
use PHPUnit\Framework\Constraint\LogicalNot;
use Tiime\NewRelicBundle\DependencyInjection\TiimeNewRelicExtension;
use Tiime\NewRelicBundle\Listener\CommandListener;
use Tiime\NewRelicBundle\Listener\DeprecationListener;
use Tiime\NewRelicBundle\Listener\ExceptionListener;
use Tiime\NewRelicBundle\NewRelic\BlackholeInteractor;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Tiime\NewRelicBundle\Twig\NewRelicExtension;

class TiimeNewRelicExtensionTest extends AbstractExtensionTestCase
{
    protected function getContainerExtensions(): array
    {
        return [new TiimeNewRelicExtension()];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->setParameter('kernel.bundles', []);
    }

    public function testDefaultConfiguration(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(NewRelicExtension::class);
        $this->assertContainerBuilderHasService(CommandListener::class);
        $this->assertContainerBuilderHasService(ExceptionListener::class);
    }

    public function testAlternativeConfiguration(): void
    {
        $this->load([
            'exceptions' => false,
            'commands' => false,
            'twig' => false,
        ]);

        $this->assertContainerBuilderNotHasService(NewRelicExtension::class);
        $this->assertContainerBuilderNotHasService(CommandListener::class);
        $this->assertContainerBuilderNotHasService(ExceptionListener::class);
    }

    public function testDeprecation(): void
    {
        $this->load();

        $this->assertContainerBuilderHasService(DeprecationListener::class);
    }

    public function testMonolog(): void
    {
        $this->load(['monolog' => true]);

        $this->assertContainerBuilderHasParameter('tiime.new_relic.monolog');
        $this->assertContainerBuilderHasParameter('tiime.new_relic.application_name');
        $this->assertContainerBuilderHasService('tiime.new_relic.logs_handler');
    }

    public function testMonologDisabled(): void
    {
        $this->load(['monolog' => false]);

        self::assertThat(
            $this->container,
            new LogicalNot(new ContainerHasParameterConstraint('tiime.new_relic.monolog', null, false))
        );
    }

    public function testConfigDisabled(): void
    {
        $this->load([
            'enabled' => false,
        ]);

        $this->assertContainerBuilderHasAlias(NewRelicInteractorInterface::class, BlackholeInteractor::class);
    }

    public function testConfigDisabledWithInteractor(): void
    {
        $this->load([
            'enabled' => false,
            'interactor' => 'tiime.new_relic.interactor.adaptive',
        ]);

        $this->assertContainerBuilderHasAlias(NewRelicInteractorInterface::class, BlackholeInteractor::class);
    }

    public function testConfigEnabledWithInteractor(): void
    {
        $this->load([
            'enabled' => true,
            'interactor' => 'tiime.new_relic.interactor.adaptive',
        ]);

        $this->assertContainerBuilderHasAlias(NewRelicInteractorInterface::class, 'tiime.new_relic.interactor.adaptive');
    }
}
