<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;

/**
 * Listen to exceptions dispatched by Symfony to log them to NewRelic.
 */
class ExceptionListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly NewRelicInteractorInterface $interactor,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 0],
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        if (!$exception instanceof HttpExceptionInterface) {
            $this->interactor->noticeThrowable($exception);
        }
    }
}
