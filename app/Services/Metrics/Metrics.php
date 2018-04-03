<?php

namespace RZP\Services\Metrics;

/**
 * Metrics service.
 * Works with various underlying driver implementation.
 *
 * Usage:
 * - Metrics::count('total_hits');                    // Usage default driver per config/metrics.php
 * - Metrics::driver('custom')->count('total_hits');  // Usage custom driver
 */
class Metrics
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @var Drivers\Driver
     */
    protected $currentDriver;

    /**
     * @var array
     */
    protected $driverPool;

    public function __construct(array $config)
    {
        $this->config = $config;

        $this->driver();
    }

    /**
     * Initializes given driver if not already and sets it to current driver
     * @param  string|null $driver
     * @return $this
     */
    public function driver(string $driver = null): Metrics
    {
        $driver = $driver ?: $this->config['driver'];
        $this->currentDriver = $this->driverPool[$driver] ?: ($this->driverPool[$driver] = $this->createDriver($driver));

        return $this;
    }

    /**
     * Invokes the underlying driver's implementation
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->currentDriver->$name(...$arguments);
    }

    /**
     * Creates the driver instance
     * @param  string $driver
     * @return Drivers\Driver
     */
    protected function createDriver(string $driver): Drivers\Driver
    {
        $impl = __NAMESPACE__ . '\\Drivers\\' . studly_case($driver);

        return new $impl($this->config[$driver]);
    }
}
