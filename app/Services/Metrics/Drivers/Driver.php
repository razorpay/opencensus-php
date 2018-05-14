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

        $this->namespace($config['namespace']);
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
     * Modifies values in dimensions. For a list of labels only allows white-
     * listed values or else usage default. This way we ensure that labels with
     * high cardinality are not cuasing troubles in monitoring system and we
     * only instrument where monitoring needed (e.g. for big merchants etc).
     *
     * @param  array $dimensions
     * @return array
     */
    public function getModifiedDimensions(array $dimensions = []): array
    {
        $defaultLabelValue = $this->config['default_label_value'];
        $whitelistedLabelValues = $this->config['whitelisted_label_values'];

        foreach ($whitelistedLabelValues as $label => $whitelist)
        {
            if ((array_key_exists($label, $dimensions) === true) and
                (in_array($dimensions[$label], $whitelist, true) === false))
            {
                $dimensions[$label] = $defaultLabelValue;
            }
        }

        return $dimensions;
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
