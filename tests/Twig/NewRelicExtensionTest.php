<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\Twig;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tiime\NewRelicBundle\NewRelic\Config;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Tiime\NewRelicBundle\Twig\NewRelicExtension;

class NewRelicExtensionTest extends TestCase
{
    private Config&MockObject $newRelic;
    private NewRelicInteractorInterface&MockObject $interactor;

    protected function setUp(): void
    {
        $this->newRelic = $this->getMockBuilder(Config::class)
        ->setMethods(['getCustomMetrics', 'getCustomParameters'])
        ->disableOriginalConstructor()
            ->getMock();
        $this->interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
    }

    /**
     * Tests the initial values returned by state methods.
     */
    public function testInitialSetup(): void
    {
        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor
        );

        $this->assertFalse($extension->isHeaderCalled());
        $this->assertFalse($extension->isFooterCalled());
        $this->assertFalse($extension->isUsed());
    }

    public function testHeaderException(): void
    {
        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor
        );

        $this->newRelic->expects($this->once())
            ->method('getCustomMetrics')
            ->willReturn([]);

        $this->newRelic->expects($this->once())
            ->method('getCustomParameters')
            ->willReturn([]);

        $this->expectException(\RuntimeException::class);

        $extension->getNewrelicBrowserTimingHeader();
        $extension->getNewrelicBrowserTimingHeader();
    }

    public function testFooterException(): void
    {
        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor
        );

        $this->newRelic->expects($this->once())
            ->method('getCustomMetrics')
            ->willReturn([]);

        $this->newRelic->expects($this->once())
            ->method('getCustomParameters')
            ->willReturn([]);

        $this->expectException(\RuntimeException::class);

        $extension->getNewrelicBrowserTimingHeader();
        $extension->getNewrelicBrowserTimingHeader();
    }

    public function testPreparingOfInteractor(): void
    {
        $headerValue = '__HEADER__TIMING__';
        $footerValue = '__FOOTER__TIMING__';

        $extension = new NewRelicExtension(
            $this->newRelic,
            $this->interactor,
            true
        );

        $this->newRelic->expects($this->once())
            ->method('getCustomMetrics')
            ->willReturn([
                'a' => 'b',
                'c' => 'd',
            ]);

        $this->newRelic->expects($this->once())
            ->method('getCustomParameters')
            ->willReturn([
                'e' => 'f',
                'g' => 'h',
                'i' => 'j',
            ]);

        $this->interactor->expects($this->once())
            ->method('disableAutoRum');

        $this->interactor->expects($this->exactly(2))
            ->method('addCustomMetric');

        $this->interactor->expects($this->exactly(3))
            ->method('addCustomParameter');

        $this->interactor->expects($this->once())
            ->method('getBrowserTimingHeader')
            ->willReturn($headerValue);

        $this->interactor->expects($this->once())
            ->method('getBrowserTimingFooter')
            ->willReturn($footerValue);

        $this->assertSame($headerValue, $extension->getNewrelicBrowserTimingHeader());
        $this->assertTrue($extension->isHeaderCalled());
        $this->assertFalse($extension->isFooterCalled());

        $this->assertSame($footerValue, $extension->getNewrelicBrowserTimingFooter());
        $this->assertTrue($extension->isHeaderCalled());
        $this->assertTrue($extension->isFooterCalled());
    }
}
