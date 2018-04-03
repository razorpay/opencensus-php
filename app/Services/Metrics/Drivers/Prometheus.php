<?php

namespace RZP\Services\Metrics\Drivers;

/**
 * Prometheus driver implementation
 */
class Prometheus extends Driver
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
}
