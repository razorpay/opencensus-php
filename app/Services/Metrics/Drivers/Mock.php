<?php

namespace RZP\Services\Metrics\Drivers;

/**
 * Mock driver implementation
 * This is default and is useful for local environment where other required(service/package) are not available.
 */
class Mock extends Driver
{
    /**
     * {@inheritDoc}
     */
    public function count(string $metric, int $times = 1, array $dimensions = []): Driver
    {
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function gauge(string $metric, float $value, array $dimensions = []): Driver
    {
        return $this;
    }

    public function histogram(string $metric, float $value, array $buckets = [], array $dimensions = []): Driver
    {
        return $this;
    }

    public function push(string $job = null)
    {
    }
}
