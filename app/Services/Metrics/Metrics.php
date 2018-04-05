<?php

namespace RZP\Services\Metrics;

use BadMethodCallException;

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
        $this->config     = $config;
        $this->driverPool = [];

        $this->driver();
    }

    /**
     * Initializes given driver if not already and sets it to current driver
     * @param  string|null $driver
     * @return $this
     */
    public function driver(string $driver = null): Metrics
    {
        $driver = $driver ?: $this->config['default'];

        if (array_key_exists($driver, $this->driverPool) == false)
        {
            $this->driverPool[$driver] = $this->createDriver($driver);
        }

        $this->currentDriver = $this->driverPool[$driver];

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
        if (method_exists($this->currentDriver, $name) === false)
        {
            throw new BadMethodCallException("Not implemented method: $name");
        }

        try
        {
            return $this->currentDriver->$name(...$arguments);
        }
        // In case it errors out, for now not doing anything. Probably log, but not want to couple with api's trace.
        // Also we would probably come to know of issues via prometheus alerts.
        catch (\Throwable $e)
        {
        }
    }

    /**
     * Creates the driver instance
     * @param  string $driver
     * @return Drivers\Driver
     */
    protected function createDriver(string $driver): Drivers\Driver
    {
        $impl = __NAMESPACE__ . '\\Drivers\\' . studly_case($driver);

        // In cases of corrupt configurations deployed, don't fail critical path. Just work with mock implementation.
        // Also no need to log here, we would come to know of monitoring not working via other means.
        if (class_exists($impl) === false)
        {
            return new Drivers\Mock;
        }

        return new $impl($this->config['drivers'][$driver]);
    }
}
