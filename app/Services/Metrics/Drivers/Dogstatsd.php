<?php

namespace RZP\Services\Metrics\Drivers;

use DataDog;

/**
 * Implements interface for Dogstatsd backed
 */
class Dogstatsd extends Driver
{
    /**
     * As we actually channel metrics to Prometheus, sampling feature is not to be used.
     */
    const SAMPLE_RATE = 1.0;

    /**
     * @var DataDog\DogStatsd
     */
    protected $statsd;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $this->statsd = new DataDog\DogStatsd($this->config['client']);
    }

    /**
     * {@inheritDoc}
     */
    public function count(string $metric, int $times = 1, array $dimensions = []): Driver
    {
        $this->statsd->increment($this->getNamespacedMetric($metric), $times, self::SAMPLE_RATE, $dimensions);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function gauge(string $metric, float $value, array $dimensions = []): Driver
    {
        $this->statsd->gauge($this->getNamespacedMetric($metric), $value, self::SAMPLE_RATE, $dimensions);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function histogram(string $metric, float $value, array $buckets = [], array $dimensions = []): Driver
    {
        // TODO: Histogram support is limited in some sense via statsd interface; To check and have fixed later;
        // For now it's reported as summary in Prometheus with default 50, 90 and 99 %ile.
        $this->statsd->histogram($this->getNamespacedMetric($metric), $value, self::SAMPLE_RATE, $dimensions);

        return $this;
    }
}
