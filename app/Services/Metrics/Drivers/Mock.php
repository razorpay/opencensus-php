<?php

namespace RZP\Services\Metrics\Drivers;

/**
 * Mock driver implementation
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
}
