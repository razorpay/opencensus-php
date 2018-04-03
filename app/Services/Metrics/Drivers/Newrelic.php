<?php

namespace RZP\Services\Metrics\Drivers;

/**
 * Newrelic driver implementation
 */
class Newrelic extends Driver
{
    /**
     * {@inheritDoc}
     */
    public function count(string $metric, int $times = 1, array $dimensions = []): Driver
    {
        // TODO: Implement

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function gauge(string $metric, float $value, array $dimensions = []): Driver
    {
        $this->invokeWithExtLoaded('newrelic_custom_metric', $metricName, $value);

        return $this;
    }

    /**
     * Invokes newrelic method if extension is loaded
     * @param  string $func
     * @param  array  $arguments
     * @return mixed
     */
    protected function invokeWithExtLoaded(string $func, ...$arguments)
    {
        if (extension_loaded('newrelic'))
        {
            $func(...$arguments);
        }
        // TODO: Log error for else condition!
    }
}
