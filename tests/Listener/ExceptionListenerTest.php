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
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tiime\NewRelicBundle\Listener\ExceptionListener;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;

class ExceptionListenerTest extends TestCase
{
    public function testOnKernelException(): void
    {
        $exception = new \Exception('Boom');

        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->once())->method('noticeThrowable')->with($exception);

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $request = new Request();

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $exception);

        $listener = new ExceptionListener($interactor);
        $listener->onKernelException($event);
    }

    public function testOnKernelExceptionWithHttp(): void
    {
        $exception = new BadRequestHttpException('Boom');

        $interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $interactor->expects($this->never())->method('noticeThrowable');

        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();
        $request = new Request();

        $event = new ExceptionEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST, $exception);

        $listener = new ExceptionListener($interactor);
        $listener->onKernelException($event);
    }
}
