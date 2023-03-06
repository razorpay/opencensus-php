<?php

namespace RZP\Services\CircuitBreaker;

use Throwable;
use RZP\Services\CircuitBreaker\Store\StoreInterface;

/**
 * Class CircuitBreaker
 *
 * @package RZP\Services\CircuitBreaker\
 */
class CircuitBreaker
{
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
        }
        catch (Throwable $e)
        {
            app('trace')->traceException($e);
        }

        return true;
    }

    public function failure(): void
    {
        try
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
        catch (Throwable $e)
        {
            app('trace')->traceException($e);
        }
    }

    public function success(): void
    {
        try
        {
            $this->store->setSuccess($this->service);
        }
        catch (Throwable $e)
        {
            app('trace')->traceException($e);
        }
    }

    public function openCircuit(): void
    {
        try
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
        catch (Throwable $e)
        {
            app('trace')->traceException($e);
        }
    }

    public function getFailuresCounter(): int
    {
        try
        {
            return $this->store->getFailuresCounter($this->service);
        }
        catch (Throwable $e)
        {
            app('trace')->traceException($e);

            return 0;
        }
    }
}
