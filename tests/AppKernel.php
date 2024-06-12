<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests;

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\Routing\RouteCollection;
use Tiime\NewRelicBundle\TiimeNewRelicBundle;

class AppKernel extends Kernel
{
    private string $cachePrefix = '';

    private ?string $fakedProjectDir = null;

    public function __construct(string $cachePrefix)
    {
        parent::__construct($cachePrefix, true);
        $this->cachePrefix = $cachePrefix;
    }

    public function getCacheDir(): string
    {
        return sys_get_temp_dir().'/tiime/'.$this->cachePrefix;
    }

    public function getLogDir(): string
    {
        return sys_get_temp_dir().'/tiime/log';
    }

    public function getProjectDir(): string
    {
        if (null === $this->fakedProjectDir) {
            return realpath(__DIR__.'/../../../../');
        }

        return $this->fakedProjectDir;
    }

    public function setProjectDir(?string $projectDir): void
    {
        $this->fakedProjectDir = $projectDir;
    }

    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new TiimeNewRelicBundle(),
        ];
    }

    /**
     * (From MicroKernelTrait)
     * {@inheritdoc}
     */
    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(function (ContainerBuilder $container): void {
            $container->loadFromExtension('framework', [
                'secret' => 'test',
                'router' => [
                    'resource' => 'kernel:loadRoutes',
                    'type' => 'service',
                ],
            ]);

            // Not setting the router to utf8 is deprecated in symfony 5.1
            if (Kernel::VERSION_ID >= 50100) {
                $container->loadFromExtension('framework', [
                    'router' => ['utf8' => true],
                ]);
            }

            // Not setting the "framework.session.storage_factory_id" configuration option is deprecated in symfony 5.3
            if (Kernel::VERSION_ID >= 50300) {
                $container->loadFromExtension('framework', [
                    'session' => ['storage_factory_id' => 'session.storage.factory.mock_file'],
                ]);
            } else {
                $container->loadFromExtension('framework', [
                    'session' => ['storage_id' => 'session.storage.mock_file'],
                ]);
            }

            $container->addObjectResource($this);
        });
    }

    /**
     * (From MicroKernelTrait).
     *
     * @internal
     */
    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        return new RouteCollection();
    }

    protected function buildContainer(): ContainerBuilder
    {
        $container = parent::buildContainer();

        $container->addCompilerPass(new class() implements CompilerPassInterface {
            public function process(ContainerBuilder $container): void
            {
                foreach ($container->getDefinitions() as $id => $definition) {
                    if (preg_match('|Tiime.*|i', $id)) {
                        $definition->setPublic(true);
                    }
                }

                foreach ($container->getAliases() as $id => $alias) {
                    if (preg_match('|Tiime.*|i', $id)) {
                        $alias->setPublic(true);
                    }
                }
            }
        });

        return $container;
    }
}
