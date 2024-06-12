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
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tiime\NewRelicBundle\NewRelic\Config;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Tiime\NewRelicBundle\TransactionNamingStrategy\TransactionNamingStrategyInterface;

class RequestListener implements EventSubscriberInterface
{
    /**
     * @param string[] $ignoredRoutes
     * @param string[] $ignoredPaths
     */
    public function __construct(
        private Config $config,
        private NewRelicInteractorInterface $interactor,
        private array $ignoredRoutes,
        private array $ignoredPaths,
        private TransactionNamingStrategyInterface $transactionNamingStrategy,
        private bool $symfonyCache = false
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => [
                ['setApplicationName', 255],
                ['setIgnoreTransaction', 31],
                ['setTransactionName', -10],
            ],
        ];
    }

    public function setApplicationName(RequestEvent $event): void
    {
        if (!$this->isEventValid($event)) {
            return;
        }

        $appName = $this->config->getName();

        if (!$appName) {
            return;
        }

        if ($this->symfonyCache) {
            $this->interactor->startTransaction($appName);
        }

        // Set application name if different from ini configuration
        if ($appName !== \ini_get('newrelic.appname')) {
            $this->interactor->setApplicationName($appName, $this->config->getLicenseKey(), $this->config->getXmit());
        }
    }

    public function setTransactionName(RequestEvent $event): void
    {
        if (!$this->isEventValid($event)) {
            return;
        }

        $transactionName = $this->transactionNamingStrategy->getTransactionName($event->getRequest());

        $this->interactor->setTransactionName($transactionName);
    }

    public function setIgnoreTransaction(RequestEvent $event): void
    {
        if (!$this->isEventValid($event)) {
            return;
        }

        $request = $event->getRequest();
        if (\in_array($request->get('_route'), $this->ignoredRoutes, true)) {
            $this->interactor->ignoreTransaction();
        }

        if (\in_array($request->getPathInfo(), $this->ignoredPaths, true)) {
            $this->interactor->ignoreTransaction();
        }
    }

    /**
     * Make sure we should consider this event. Example: make sure it is a master request.
     */
    private function isEventValid(RequestEvent $event): bool
    {
        return HttpKernelInterface::MAIN_REQUEST === $event->getRequestType();
    }
}
