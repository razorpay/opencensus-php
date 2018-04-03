<?php

namespace RZP\Services\Metrics\Drivers;

use Prometheus\Storage\APC;
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

    // TODO: Take which adapter to use from config

    /**
     * Flushes adapter storage
     */
    public function flush()
    {
        (new APC)->flushAPC();
    }

    protected function createRegistry(): CollectorRegistry
    {
        return new CollectorRegistry(new APC);
    }
}
