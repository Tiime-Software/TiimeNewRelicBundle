<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\TransactionNamingStrategy;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tiime\NewRelicBundle\TransactionNamingStrategy\ControllerNamingStrategy;

class ControllerNamingStrategyTest extends TestCase
{
    public function testControllerAsString(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', 'SomeBundle:Some:SomeAction');

        $strategy = new ControllerNamingStrategy();
        $this->assertSame('SomeBundle:Some:SomeAction', $strategy->getTransactionName($request));
    }

    public function testControllerAsClosure(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', function () {
        });

        $strategy = new ControllerNamingStrategy();
        $this->assertSame('Closure controller', $strategy->getTransactionName($request));
    }

    public function testControllerAsCallback(): void
    {
        $request = new Request();
        $request->attributes->set('_controller', [$this, 'testControllerAsString']);

        $strategy = new ControllerNamingStrategy();
        $this->assertSame('Callback controller: Tiime\NewRelicBundle\Tests\TransactionNamingStrategy\ControllerNamingStrategyTest::testControllerAsString()', $strategy->getTransactionName($request));
    }

    public function testControllerUnknown(): void
    {
        $request = new Request();

        $strategy = new ControllerNamingStrategy();
        $this->assertSame('Unknown Symfony controller', $strategy->getTransactionName($request));
    }
}
