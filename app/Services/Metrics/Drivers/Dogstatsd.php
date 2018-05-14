<?php

namespace RZP\Services\Metrics\Drivers;

use DataDog;

/**
 * Implements interface for Dogstatsd backend
 */
class Dogstatsd extends Driver
{
    /**
     * As we actually channel metrics to Prometheus, sampling feature is not to be used.
     * Sample rate? Say this value is 0.5, client like (statsd etc) will send any metric 50% of the times.
     */
    const SAMPLE_RATE = 1.0;

    /**
     * @var DataDog\DogStatsd
     */
    protected $statsd;

    public function __construct(array $config = [])
    {
        parent::__construct($config);

        $statsdClientOptions = $config['drivers']['dogstatsd']['client'];
        $this->statsd = new DataDog\DogStatsd($statsdClientOptions);
    }

    /**
     * {@inheritDoc}
     */
    public function count(string $metric, int $times = 1, array $dimensions = [])
    {
        while ($times--)
        {
            $this->statsd->increment(
                $this->getNamespacedMetric($metric),
                self::SAMPLE_RATE,
                $this->getModifiedDimensions($dimensions));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function gauge(string $metric, float $value, array $dimensions = [])
    {
        $this->statsd->gauge(
            $this->getNamespacedMetric($metric),
            $value,
            self::SAMPLE_RATE,
            $this->getModifiedDimensions($dimensions));
    }

    /**
     * {@inheritDoc}
     */
    public function histogram(string $metric, float $value, array $dimensions = [])
    {
        // TODO: Histogram support is limited in some sense via statsd interface; To check and have fixed later;
        // For now it's reported as summary in Prometheus with default 50, 90 and 99 %ile.
        $this->statsd->histogram(
            $this->getNamespacedMetric($metric),
            $value,
            self::SAMPLE_RATE,
            $this->getModifiedDimensions($dimensions));
    }
}
