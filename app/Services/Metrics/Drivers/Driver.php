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


    public function __construct(array $config)
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
     * @param  int    $times
     * @param  array  $dimensions
     * @return Driver
     */
    abstract public function count(string $metric, int $times = 1, array $dimensions = []): Driver;

    /**
     * @param  string $metric
     * @param  float  $value
     * @param  array  $dimensions
     * @return Driver
     */
    abstract public function gauge(string $metric, float $value, array $dimensions = []): Driver;
}
