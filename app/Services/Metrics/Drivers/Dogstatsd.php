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
     * Note: Histogram of dogstatsd is reported as Prometheus summary metric type. If needed, we can define mapping &
     * buckets for specific metric name/pattern in statsd_mapping.yml file.
     * {@inheritDoc}
     */
    public function histogram(string $metric, float $value, array $dimensions = [])
    {
        $this->statsd->histogram(
            $this->getNamespacedMetric($metric),
            $value,
            self::SAMPLE_RATE,
            $this->getModifiedDimensions($dimensions));
    }

    /**
     * {@inheritDoc}
     */
    public function summary(string $metric, float $value, array $dimensions = [])
    {
        // Dogstatsd timing() emulates the behavior of Prometheus summary
        $this->statsd->timing(
            $this->getNamespacedMetric($metric),
            $value,
            self::SAMPLE_RATE,
            $this->getModifiedDimensions($dimensions));
    }
}
