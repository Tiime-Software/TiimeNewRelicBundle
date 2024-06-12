<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests;

use PHPUnit\Framework\TestCase;
use Tiime\NewRelicBundle\NewRelic\AdaptiveInteractor;
use Tiime\NewRelicBundle\NewRelic\BlackholeInteractor;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractor;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Tiime\NewRelicBundle\TiimeNewRelicBundle;

/**
 * Smoke test to see if the bundle can run.
 *
 * @author Tobias Nyholm <tobias.nyholm@gmail.com>
 */
class BundleInitializationTest extends TestCase
{
    protected function getBundleClass(): string
    {
        return TiimeNewRelicBundle::class;
    }

    public function testInitBundle(): void
    {
        $kernel = new AppKernel(uniqid('cache'));
        $kernel->boot();

        // Get the container
        $container = $kernel->getContainer();

        $services = [
            NewRelicInteractorInterface::class => AdaptiveInteractor::class,
            BlackholeInteractor::class,
            NewRelicInteractor::class,
        ];

        // Test if you services exists
        foreach ($services as $id => $class) {
            if (\is_int($id)) {
                $id = $class;
            }
            $this->assertTrue($container->has($id));
            $service = $container->get($id);
            $this->assertInstanceOf($class, $service);
        }
    }
}
