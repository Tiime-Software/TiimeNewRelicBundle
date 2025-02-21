<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Logging;

use Monolog\Handler\NewRelicHandler;
use Monolog\LogRecord;
use Psr\Log\LogLevel;

class AdaptiveHandler extends NewRelicHandler
{
    public function __construct(
        string $level = LogLevel::ERROR,
        bool $bubble = true,
        ?string $appName = null,
        bool $explodeArrays = false,
        ?string $transactionName = null,
    ) {
        parent::__construct($level, $bubble, $appName, $explodeArrays, $transactionName);
    }

    protected function write(LogRecord $record): void
    {
        if (!$this->isNewRelicEnabled()) {
            return;
        }

        parent::write($record);
    }
}
