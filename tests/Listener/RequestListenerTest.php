<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\Listener;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tiime\NewRelicBundle\Listener\RequestListener;
use Tiime\NewRelicBundle\NewRelic\Config;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Tiime\NewRelicBundle\TransactionNamingStrategy\TransactionNamingStrategyInterface;

class RequestListenerTest extends TestCase
{
    public function testSubRequest(): void
    {
        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->never())->method('setTransactionName');

        $namingStrategy = $this->getMockBuilder(TransactionNamingStrategyInterface::class)->getMock();

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::SUB_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy);
        $listener->setApplicationName($event);
    }

    public function testMasterRequest(): void
    {
        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->once())->method('setTransactionName');

        $namingStrategy = $this->getMockBuilder(TransactionNamingStrategyInterface::class)
            ->setMethods(['getTransactionName'])
            ->getMock();
        $namingStrategy->expects($this->once())->method('getTransactionName')->willReturn('foobar');

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy);
        $listener->setTransactionName($event);
    }

    public function testPathIsIgnored(): void
    {
        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->once())->method('ignoreTransaction');

        $namingStrategy = $this->getMockBuilder(TransactionNamingStrategyInterface::class)->getMock();

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => '/ignored_path']);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], ['/ignored_path'], $namingStrategy);
        $listener->setIgnoreTransaction($event);
    }

    public function testRouteIsIgnored(): void
    {
        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->once())->method('ignoreTransaction');

        $namingStrategy = $this->getMockBuilder(TransactionNamingStrategyInterface::class)->getMock();

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $request = new Request([], [], ['_route' => 'ignored_route']);

        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, ['ignored_route'], [], $namingStrategy);
        $listener->setIgnoreTransaction($event);
    }

    public function testSymfonyCacheEnabled(): void
    {
        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->once())->method('startTransaction');

        $namingStrategy = $this->getMockBuilder(TransactionNamingStrategyInterface::class)->getMock();

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy, true);
        $listener->setApplicationName($event);
    }

    public function testSymfonyCacheDisabled(): void
    {
        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->never())->method('startTransaction');

        $namingStrategy = $this->getMockBuilder(TransactionNamingStrategyInterface::class)->getMock();

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        $event = new RequestEvent($kernel, new Request(), HttpKernelInterface::MAIN_REQUEST);

        $listener = new RequestListener(new Config('App name', 'Token'), $interactor, [], [], $namingStrategy, false);
        $listener->setApplicationName($event);
    }
}
