<?php

namespace RZP\Services\Metrics\Drivers;

/**
 * Base driver class
 */
abstract class Driver
{
    /**
     * @var array
     */
    protected $config;

    /**
     * Namespace under which metrics are being collected
     * @var string
     */
    protected $namespace;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * @param  string $namespace
     * @return Driver
     */
    public function namespace(string $namespace): Driver
    {
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @param  string $metric
     * @return string
     */
    public function getNamespacedMetric(string $metric): string
    {
        return "{$this->namespace}_{$metric}";
    }

    /**
     * @param  string $metric
     * @param  int    $times
     * @param  array  $dimensions
     */
    abstract public function count(string $metric, int $times, array $dimensions = []);

    /**
     * @param  string $metric
     * @param  float  $value
     * @param  array  $dimensions
     */
    abstract public function gauge(string $metric, float $value, array $dimensions = []);

    /**
     * @param  string $metric
     * @param  float  $value
     * @param  array  $dimensions
     */
    abstract public function histogram(string $metric, float $value, array $dimensions = []);
}
