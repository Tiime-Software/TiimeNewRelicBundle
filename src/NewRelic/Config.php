<?php

declare(strict_types=1);

/*
 * This file is part of Ekino New Relic bundle.
 *
 * (c) Ekino - Thomas Rabaix <thomas.rabaix@ekino.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ekino\NewRelicBundle\NewRelic;

/**
 * This value object contains data and configuration that should be passed to the interactors.
 */
class Config
{
    private string $name;
    private ?string $apiKey;
    private ?string $apiHost;
    private string $licenseKey;
    private bool $xmit;

    /**
     * @var array<string, array<mixed>>
     */
    private array $customEvents;

    /**
     * @var array<string, float>
     */
    private array $customMetrics;

    /**
     * @var array<string, scalar>
     */
    private array $customParameters;

    /**
     * @var string[]
     */
    private array $deploymentNames;

    /**
     * @param string[] $deploymentNames
     */
    public function __construct(?string $name, ?string $apiKey = null, ?string $licenseKey = null, bool $xmit = false, array $deploymentNames = [], ?string $apiHost = null)
    {
        $this->name = (!empty($name) ? $name : \ini_get('newrelic.appname')) ?: '';
        $this->apiKey = $apiKey;
        $this->apiHost = $apiHost;
        $this->licenseKey = (!empty($licenseKey) ? $licenseKey : \ini_get('newrelic.license')) ?: '';
        $this->xmit = $xmit;
        $this->deploymentNames = $deploymentNames;
        $this->customEvents = [];
        $this->customMetrics = [];
        $this->customParameters = [];
    }

    /**
     * @param array<string, array<mixed>> $customEvents
     */
    public function setCustomEvents(array $customEvents): void
    {
        $this->customEvents = $customEvents;
    }

    /**
     * @return array<string, array<mixed>>
     */
    public function getCustomEvents(): array
    {
        return $this->customEvents;
    }

    /**
     * @param array<string, scalar> $attributes
     */
    public function addCustomEvent(string $name, array $attributes): void
    {
        $this->customEvents[$name][] = $attributes;
    }

    /**
     * @param array<string, float> $customMetrics
     */
    public function setCustomMetrics(array $customMetrics): void
    {
        $this->customMetrics = $customMetrics;
    }

    /**
     * @return array<string, float>
     */
    public function getCustomMetrics(): array
    {
        return $this->customMetrics;
    }

    /**
     * @param array<string, scalar> $customParameters
     */
    public function setCustomParameters(array $customParameters): void
    {
        $this->customParameters = $customParameters;
    }

    public function addCustomParameter(string $name, string|int|float|bool $value): void
    {
        $this->customParameters[$name] = $value;
    }

    public function addCustomMetric(string $name, float $value): void
    {
        $this->customMetrics[$name] = $value;
    }

    /**
     * @return array<string, scalar>
     */
    public function getCustomParameters(): array
    {
        return $this->customParameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string[]
     */
    public function getDeploymentNames(): array
    {
        return $this->deploymentNames;
    }

    public function getApiKey(): ?string
    {
        return $this->apiKey;
    }

    public function getApiHost(): ?string
    {
        return $this->apiHost;
    }

    public function getLicenseKey(): ?string
    {
        return $this->licenseKey;
    }

    public function getXmit(): bool
    {
        return $this->xmit;
    }
}
