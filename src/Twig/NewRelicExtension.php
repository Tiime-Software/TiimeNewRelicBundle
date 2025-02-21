<?php

declare(strict_types=1);

/*
 * This file is part of Tiime New Relic bundle.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tiime\NewRelicBundle\Twig;

use Tiime\NewRelicBundle\NewRelic\Config;
use Tiime\NewRelicBundle\NewRelic\NewRelicInteractorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Twig extension to manually include BrowserTimingHeader and BrowserTimingFooter into twig templates.
 */
class NewRelicExtension extends AbstractExtension
{
    private bool $headerCalled = false;
    private bool $footerCalled = false;

    public function __construct(
        private readonly Config $newRelic,
        private readonly NewRelicInteractorInterface $interactor,
        private readonly bool $instrument = false,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('tiime_newrelic_browser_timing_header', [$this, 'getNewrelicBrowserTimingHeader'], ['is_safe' => ['html']]),
            new TwigFunction('tiime_newrelic_browser_timing_footer', [$this, 'getNewrelicBrowserTimingFooter'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @throws \RuntimeException
     */
    public function getNewrelicBrowserTimingHeader(): string
    {
        if ($this->isHeaderCalled()) {
            throw new \RuntimeException('Function "tiime_newrelic_browser_timing_header" has already been called');
        }

        $this->prepareInteractor();

        $this->headerCalled = true;

        return $this->interactor->getBrowserTimingHeader();
    }

    /**
     * @throws \RuntimeException
     */
    public function getNewrelicBrowserTimingFooter(): string
    {
        if ($this->isFooterCalled()) {
            throw new \RuntimeException('Function "tiime_newrelic_browser_timing_footer" has already been called');
        }

        if (false === $this->isHeaderCalled()) {
            $this->prepareInteractor();
        }

        $this->footerCalled = true;

        return $this->interactor->getBrowserTimingFooter();
    }

    public function isHeaderCalled(): bool
    {
        return $this->headerCalled;
    }

    public function isFooterCalled(): bool
    {
        return $this->footerCalled;
    }

    public function isUsed(): bool
    {
        return $this->isHeaderCalled() || $this->isFooterCalled();
    }

    private function prepareInteractor(): void
    {
        if ($this->instrument) {
            $this->interactor->disableAutoRUM();
        }

        foreach ($this->newRelic->getCustomMetrics() as $name => $value) {
            $this->interactor->addCustomMetric((string) $name, (float) $value);
        }

        foreach ($this->newRelic->getCustomParameters() as $name => $value) {
            $this->interactor->addCustomParameter((string) $name, $value);
        }
    }
}
