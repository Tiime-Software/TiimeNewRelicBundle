<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Listener;

use Tiime\NewRelicBundle\Exception\DeprecationException;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;

class DeprecationListener
{
    private bool $isRegistered = false;

    public function __construct(
        private readonly NewRelicInteractorInterface $interactor,
    ) {
    }

    public function register(): void
    {
        if ($this->isRegistered) {
            return;
        }
        $this->isRegistered = true;

        $prevErrorHandler = set_error_handler(function ($type, $msg, $file, $line, $context = []) use (&$prevErrorHandler) {
            if (\E_USER_DEPRECATED === $type) {
                $this->interactor->noticeThrowable(new DeprecationException($msg, 0, $type, $file, $line));
            }

            return $prevErrorHandler ? $prevErrorHandler($type, $msg, $file, $line, $context) : false;
        });
    }

    public function unregister(): void
    {
        if (!$this->isRegistered) {
            return;
        }
        $this->isRegistered = false;
        restore_error_handler();
    }
}
