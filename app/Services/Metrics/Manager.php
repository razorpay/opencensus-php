<?php

namespace RZP\Services\Metrics;

use Illuminate\Support\Manager as IlluminateManager;

/**
 * Metrics manager. Works with various underlying driver implementation.
 */
class Manager extends IlluminateManager
{
    /**
     * @var array
     */
    protected $config;

    /**
     * {@inheritDoc}
     */
    public function __construct($app)
    {
        parent::__construct($app);

        $this->config = $app->config->get('metrics');
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDriver()
    {
        return $this->config['default'];
    }

    /**
     * {@inheritDoc}
     */
    public function __call($method, $parameters)
    {
        try
        {
            parent::__call($method, $parameters);
        }
        // In case it errors out, for now not doing anything, maybe log it but not want to couple with api's trace
        // Also we would come to know of issues via Prometheus alerts
        catch (\Throwable $e)
        {
        }
    }

    /**
     * {@inheritDoc}
     * @return Drivers\Driver
     */
    protected function createDriver($driver)
    {
        $impl = __NAMESPACE__ . '\\Drivers\\' . studly_case($driver);

        // In cases of corrupt configurations deployed, don't fail critical path. Just work with mock implementation.
        // Also no need to log here, we would come to know of monitoring not working via other means.
        $config   = $this->config['drivers'][$driver];
        $instance = class_exists($impl) === true ? new $impl($config) : new Drivers\Mock($config);
        $instance->namespace($this->config['namespace']);

        return $instance;
    }
}
