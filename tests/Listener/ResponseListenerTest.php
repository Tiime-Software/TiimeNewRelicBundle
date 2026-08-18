<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Tests\Listener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Tiime\NewRelicBundle\Listener\ResponseListener;
use Tiime\NewRelicBundle\NewRelic\Config;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Tiime\NewRelicBundle\Twig\NewRelicExtension;

class ResponseListenerTest extends TestCase
{
    private NewRelicInteractorInterface&MockObject $interactor;
    private Config&MockObject $newRelic;
    private NewRelicExtension&MockObject $extension;

    protected function setUp(): void
    {
        $this->interactor = $this->getMockBuilder(NewRelicInteractorInterface::class)->getMock();
        $this->newRelic = $this->getMockBuilder(Config::class)
            ->onlyMethods(['getCustomEvents', 'getCustomMetrics', 'getCustomParameters'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = $this->getMockBuilder(NewRelicExtension::class)
            ->onlyMethods(['isHeaderCalled', 'isFooterCalled', 'isUsed'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testOnKernelResponseOnlyMasterRequestsAreProcessed(): void
    {
        $event = $this->createFilterResponseEventDummy(null, null, HttpKernelInterface::SUB_REQUEST);

        $object = new ResponseListener($this->newRelic, $this->interactor);
        $object->onKernelResponse($event);

        $this->newRelic->expects($this->never())->method('getCustomMetrics');
    }

    public function testOnKernelResponseWithOnlyCustomMetricsAndParameters(): void
    {
        $events = [
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

        $metrics = [
            'foo_a' => 4.7,
            'foo_b' => 11,
        ];

        $parameters = [
            'foo_1' => 'bar_1',
            'foo_2' => 'bar_2',
        ];

        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn($events);
        $this->newRelic->expects($this->once())->method('getCustomMetrics')->willReturn($metrics);
        $this->newRelic->expects($this->once())->method('getCustomParameters')->willReturn($parameters);

        $this->interactor->expects($this->exactly(2))->method('addCustomMetric')->willReturnCallback(
            $this->sequentialAssertionCallback([
                ['foo_a', 4.7],
                ['foo_b', 11.0],
            ], true)
        );
        $this->interactor->expects($this->exactly(2))->method('addCustomParameter')->willReturnCallback(
            $this->sequentialAssertionCallback([
                ['foo_1', 'bar_1'],
                ['foo_2', 'bar_2'],
            ], true)
        );
        $this->interactor->expects($this->exactly(2))->method('addCustomEvent')->willReturnCallback(
            $this->sequentialAssertionCallback([
                ['WidgetSale', [
                    'color' => 'red',
                    'weight' => 12.5,
                ]],
                ['WidgetSale', [
                    'color' => 'blue',
                    'weight' => 12.5,
                ]],
            ])
        );

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, false);
        $object->onKernelResponse($event);
    }

    public function testOnKernelResponseInstrumentDisabledInRequest(): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->once())->method('disableAutoRUM');

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, true);
        $object->onKernelResponse($event);
    }

    public function testSymfonyCacheEnabled(): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->once())->method('endTransaction');

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, false, true);
        $object->onKernelResponse($event);
    }

    public function testSymfonyCacheDisabled(): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->never())->method('endTransaction');

        $event = $this->createFilterResponseEventDummy();

        $object = new ResponseListener($this->newRelic, $this->interactor, false, false);
        $object->onKernelResponse($event);
    }

    #[DataProvider('providerOnKernelResponseOnlyInstrumentHTMLResponses')]
    public function testOnKernelResponseOnlyInstrumentHTMLResponses(?string $content, ?string $expectsSetContent, string $contentType): void
    {
        $this->setupNoCustomMetricsOrParameters();

        $this->interactor->expects($this->once())->method('disableAutoRUM');
        $this->interactor->expects($this->any())->method('getBrowserTimingHeader')->willReturn('__Timing_Header__');
        $this->interactor->expects($this->any())->method('getBrowserTimingFooter')->willReturn('__Timing_Feader__');

        $response = $this->createResponse($content ?? '', $contentType);
        $event = $this->createFilterResponseEventDummy(null, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true);
        $object->onKernelResponse($event);

        $this->assertSame($expectsSetContent ?? ($content ?? ''), $response->getContent());
    }

    /**
     * @return array<array{?string, ?string, string}>
     */
    public static function providerOnKernelResponseOnlyInstrumentHTMLResponses(): array
    {
        return [
            // unsupported content types
            [null, null, 'text/xml'],
            [null, null, 'text/plain'],
            [null, null, 'application/json'],

            ['content', 'content', 'text/html'],
            ['<div class="head">head</div>', '<div class="head">head</div>', 'text/html'],
            ['<header>content</header>', '<header>content</header>', 'text/html'],

            // head, body tags
            ['<head><title /></head>', '<head>__Timing_Header__<title /></head>', 'text/html'],
            ['<body><div /></body>', '<body><div />__Timing_Feader__</body>', 'text/html'],
            ['<head><title /></head><body><div /></body>', '<head>__Timing_Header__<title /></head><body><div />__Timing_Feader__</body>', 'text/html'],

            // with charset
            ['<head><title /></head><body><div /></body>', '<head>__Timing_Header__<title /></head><body><div />__Timing_Feader__</body>', 'text/html; charset=UTF-8'],
        ];
    }

    public function testInteractionWithTwigExtensionHeader(): void
    {
        $this->newRelic->expects($this->never())->method('getCustomMetrics');
        $this->newRelic->expects($this->never())->method('getCustomParameters');
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);

        $this->interactor->expects($this->never())->method('disableAutoRUM');
        $this->interactor->expects($this->never())->method('getBrowserTimingHeader');
        $this->interactor->expects($this->once())->method('getBrowserTimingFooter')->willReturn('__Timing_Feader__');

        $this->extension->expects($this->exactly(2))->method('isUsed')->willReturn(true);
        $this->extension->expects($this->once())->method('isHeaderCalled')->willReturn(true);
        $this->extension->expects($this->once())->method('isFooterCalled')->willReturn(false);

        $request = $this->createRequestMock(true);
        $response = $this->createResponse('content');
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createFilterResponseEventDummy($request, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true, false, $this->extension);
        $object->onKernelResponse($event);
    }

    public function testInteractionWithTwigExtensionFooter(): void
    {
        $this->newRelic->expects($this->never())->method('getCustomMetrics');
        $this->newRelic->expects($this->never())->method('getCustomParameters');
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);

        $this->interactor->expects($this->never())->method('disableAutoRUM');
        $this->interactor->expects($this->once())->method('getBrowserTimingHeader')->willReturn('__Timing_Feader__');
        $this->interactor->expects($this->never())->method('getBrowserTimingFooter');

        $this->extension->expects($this->exactly(2))->method('isUsed')->willReturn(true);
        $this->extension->expects($this->once())->method('isHeaderCalled')->willReturn(false);
        $this->extension->expects($this->once())->method('isFooterCalled')->willReturn(true);

        $request = $this->createRequestMock(true);
        $response = $this->createResponse('content');
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createFilterResponseEventDummy($request, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true, false, $this->extension);
        $object->onKernelResponse($event);
    }

    public function testInteractionWithTwigExtensionHeaderFooter(): void
    {
        $this->newRelic->expects($this->never())->method('getCustomMetrics');
        $this->newRelic->expects($this->never())->method('getCustomParameters');
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);

        $this->interactor->expects($this->never())->method('disableAutoRUM');
        $this->interactor->expects($this->never())->method('getBrowserTimingHeader');
        $this->interactor->expects($this->never())->method('getBrowserTimingFooter');

        $this->extension->expects($this->exactly(2))->method('isUsed')->willReturn(true);
        $this->extension->expects($this->once())->method('isHeaderCalled')->willReturn(true);
        $this->extension->expects($this->once())->method('isFooterCalled')->willReturn(true);

        $request = $this->createRequestMock(true);
        $response = $this->createResponse('content');
        $response->headers->set('Content-Type', 'text/html');
        $event = $this->createFilterResponseEventDummy($request, $response);

        $object = new ResponseListener($this->newRelic, $this->interactor, true, false, $this->extension);
        $object->onKernelResponse($event);
    }

    private function setUpNoCustomMetricsOrParameters(): void
    {
        $this->newRelic->expects($this->once())->method('getCustomEvents')->willReturn([]);
        $this->newRelic->expects($this->once())->method('getCustomMetrics')->willReturn([]);
        $this->newRelic->expects($this->once())->method('getCustomParameters')->willReturn([]);

        $this->interactor->expects($this->never())->method('addCustomEvent');
        $this->interactor->expects($this->never())->method('addCustomMetric');
        $this->interactor->expects($this->never())->method('addCustomParameter');
    }

    private function createRequestMock(bool $instrumentEnabled = true): Request
    {
        $request = new Request();
        $request->attributes = new ParameterBag(['_instrument' => $instrumentEnabled]);

        return $request;
    }

    private function createResponse(string $content = '', string $contentType = 'text/html'): Response
    {
        $response = new Response($content);
        $response->headers = new ResponseHeaderBag(['Content-Type' => $contentType]);

        return $response;
    }

    private function createFilterResponseEventDummy(?Request $request = null, ?Response $response = null, int $requestType = HttpKernelInterface::MAIN_REQUEST): ResponseEvent
    {
        $kernel = $this->getMockBuilder(HttpKernelInterface::class)->getMock();

        return new ResponseEvent($kernel, $request ?? new Request(), $requestType, $response ?? new Response());
    }

    /**
     * @param array<int, array<int, mixed>> $expectedCalls
     */
    private function sequentialAssertionCallback(array $expectedCalls, mixed $returnValue = null): \Closure
    {
        $index = 0;

        return static function (...$arguments) use (&$index, $expectedCalls, $returnValue): mixed {
            self::assertArrayHasKey($index, $expectedCalls);
            self::assertSame($expectedCalls[$index], $arguments);
            ++$index;

            return $returnValue;
        };
    }
}
