<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\TransactionNamingStrategy;

use Symfony\Component\HttpFoundation\Request;

interface TransactionNamingStrategyInterface
{
    public function getTransactionName(Request $request): string;
}
