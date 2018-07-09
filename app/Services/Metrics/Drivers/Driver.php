<?php

namespace RZP\Services\Metrics\Drivers;

use Razorpay\EC2Metadata\Ec2MetadataGetter;

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
     * Modifies dimensions in some ways as commented below
     * @param  array $dimensions
     * @return array
     */
    public function getModifiedDimensions(array $dimensions = []): array
    {
        //
        // Modifies values in dimensions. For a list of labels only allows white-listed values or else uses default.
        // This way we ensure that labels with high cardinality are not causing issues in monitoring system and we
        // only instrument where monitoring is needed (e.g. for big merchants etc).
        //

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

        //
        // Adds instance tag in each metrics because our current infra setup is in such a way that we loose this label.
        // Prometheus has honor_labels configuration set to true for this. Later we will have this removed.
        //

        $ec2 = new Ec2MetadataGetter(config('trace.cache'));

        if (config('trace.cloud') === false)
        {
            $ec2->allowDummy();
        }

        // Must use getMultiple() method because that only usage the cache
        $dimensions['instance'] = $ec2->getMultiple(['LocalIpv4'])['LocalIpv4'] ?? 'other';

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
