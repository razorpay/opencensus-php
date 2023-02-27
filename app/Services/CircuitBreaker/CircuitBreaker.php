<?php

namespace RZP\Services\CircuitBreaker;

use App;
use RZP\Services\CircuitBreaker\Store\StoreInterface;

/**
 * Class CircuitBreaker
 *
 * @package RZP\Services\CircuitBreaker\
 */
class CircuitBreaker
{
    public const TIME_WINDOW = 60;
    public const FAILURE_RATE_THRESHOLD = 50;
    public const INTERVAL_TO_HALF_OPEN = 30;

    /** @var StoreInterface $circuitBreaker */
    protected StoreInterface $store;

    protected string $service;

    /** @var array $settings Circuit Breaker settings. */
    protected array $settings;

    /**
     * CircuitBreaker constructor.
     *
     * @param CircuitBreakerStore $circuitBreaker
     * @param array               $settings Custom settings.
     */
    public function __construct(
        StoreInterface $store,
        string $service,
        array $settings = []
    )
    {
        $this->store = $store;

        $this->service = $service;

        $this->changeSettings($service);
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    public function setService(string $service)
    {
        $this->service = $service;
    }

    /**
     * Set global settings for all services
     *
     * @param string $settings
     * @return void
     */
    public function changeSettings(string $service): void
    {
        $cbConfig = config('circuit_breaker');

        $this->settings = $cbConfig[$service] ?? $cbConfig['default'];
    }

    public function getSetting(string $name)
    {
        return $this->settings[$name];
    }

    public function isAvailable(): bool
    {
        try
        {
            if ($this->store->isOpen($this->service))
            {
                return false;
            }

            $reachRateLimit = $this->store->reachRateLimit(
                $this->service,
                $this->getSetting('failure_rate_threshold')
            );

            if ($reachRateLimit === true)
            {
                $this->openCircuit();
                return false;
            }

            return true;
        }
        catch (\Exception $e)
        {

        }

    }

    public function failure(): void
    {
        $isHalfOpen = $this->store->isHalfOpen($this->service);

        if ($isHalfOpen === true)
        {
            $this->openCircuit();
            return;
        }

        $this->store->incrementFailure(
            $this->service,
            $this->getSetting('time_window')
        );
    }

    public function success(): void
    {
        $this->store->setSuccess($this->service);
    }

    public function openCircuit(): void
    {
        $this->store->setOpenCircuit(
            $this->service,
            $this->getSetting('time_window')
        );

        $this->store->setHalfOpenCircuit(
            $this->service,
            $this->getSetting('time_window'),
            $this->getSetting('interval_to_half_open')
        );
    }

    public function getFailuresCounter(): int
    {
        return $this->store->getFailuresCounter($this->service);
    }
}
