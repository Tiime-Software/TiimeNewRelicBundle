<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\NewRelic;

use PHPUnit\Framework\TestCase;
use Tiime\NewRelicBundle\NewRelic\Config;

class ConfigTest extends TestCase
{
    public function testGeneric(): void
    {
        $newRelic = new Config('Tiime', 'XXX', null, false, [], 'api.host');

        $this->assertSame('Tiime', $newRelic->getName());
        $this->assertSame('XXX', $newRelic->getApiKey());
        $this->assertSame('api.host', $newRelic->getApiHost());

        $this->assertEmpty($newRelic->getCustomEvents());
        $this->assertEmpty($newRelic->getCustomMetrics());
        $this->assertEmpty($newRelic->getCustomParameters());

        $newRelic->addCustomEvent('WidgetSale', ['color' => 'red', 'weight' => 12.5]);
        $newRelic->addCustomEvent('WidgetSale', ['color' => 'blue', 'weight' => 12.5]);

        $expected = [
            'WidgetSale' => [
                [
                    'color' => 'red',
                    'weight' => 12.5,
                ],
                [
                    'color' => 'blue',
                    'weight' => 12.5,
                ],
            ],
        ];

        $this->assertSame($expected, $newRelic->getCustomEvents());

        $newRelic->addCustomMetric('foo', 4.2);
        $newRelic->addCustomMetric('asd', 1);

        $expected = [
            'foo' => 4.2,
            'asd' => 1.0,
        ];

        $this->assertSame($expected, $newRelic->getCustomMetrics());

        $newRelic->addCustomParameter('param1', 1);

        $expected = [
            'param1' => 1,
        ];

        $this->assertSame($expected, $newRelic->getCustomParameters());
    }

    public function testDefaults(): void
    {
        $newRelic = new Config('', '');

        $this->assertNotNull($newRelic->getName());
        $this->assertSame(\ini_get('newrelic.appname') ?: '', $newRelic->getName());

        $this->assertNotNull($newRelic->getLicenseKey());
        $this->assertSame(\ini_get('newrelic.license') ?: '', $newRelic->getLicenseKey());

        $this->assertNull($newRelic->getApiHost());
    }
}
