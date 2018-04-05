<?php

namespace RZP\Services\Metrics\Drivers;

use Prometheus\PushGateway;
use Prometheus\Storage\APC;
use Prometheus\Storage\Adapter;
use Prometheus\Storage\InMemory;
use Prometheus\RenderTextFormat;
use Prometheus\CollectorRegistry;

/**
 * Prometheus driver implementation
 */
class Prometheus extends Driver
{
    /**
     * @var CollectorRegistry
     */
    protected $registry;

    public function __construct(array $config)
    {
        parent::__construct($config);

        $this->registry = $this->createRegistry();
    }

    /**
     * {@inheritDoc}
     */
    public function count(string $metric, int $times = 1, array $dimensions = []): Driver
    {
        $this->registry
             ->getOrRegisterCounter($this->namespace, $metric, null, array_keys($dimensions))
             ->incBy($times, array_values($dimensions));

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function gauge(string $metric, float $value, array $dimensions = []): Driver
    {
        $this->registry
             ->getOrRegisterGauge($this->namespace, $metric, null, array_keys($dimensions))
             ->set($value, array_values($dimensions));

        return $this;
    }

    /**
     * Renders as string metric samples, to be exposed for Prometheus to poll
     * @return string
     */
    public function render(): string
    {
        $samples = $this->registry->getMetricFamilySamples();

        return (new RenderTextFormat())->render($samples);
    }

    /**
     * Pushes collected metrics to configured pushgateway
     * @param string $job
     */
    public function push(string $job)
    {
        (new PushGateway($this->config['pushgateway']))->push($this->registry, $job);
    }

    /**
     * Flushes adapter storage
     */
    public function flush()
    {
        $this->createAdapter()->flush();
    }

    protected function createRegistry(): CollectorRegistry
    {
        return new CollectorRegistry($this->createAdapter());
    }

    protected function createAdapter(): Adapter
    {
        $adapter = $this->config['adapter'];

        switch ($adapter)
        {
            case 'apcu':
                return new APC;

            // Defaults to in memory adapter. Metrics module on it's own must never fail.
            // We can live with some data loss but critical application path is too costly to get an error because
            // of metrics module.
            default:
                return new InMemory;
        }
    }
}
