<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tiime\NewRelicBundle\DependencyInjection\Compiler\MonologHandlerPass;
use Tiime\NewRelicBundle\Listener\DeprecationListener;

class TiimeNewRelicBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new MonologHandlerPass());
    }

    public function boot(): void
    {
        parent::boot();

        if ($this->container->has(DeprecationListener::class)) {
            $this->container->get(DeprecationListener::class)->register();
        }
    }

    public function shutdown(): void
    {
        if ($this->container->has(DeprecationListener::class)) {
            $this->container->get(DeprecationListener::class)->unregister();
        }

        parent::shutdown();
    }
}
