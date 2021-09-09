<?php

namespace RZP\Trace\Metrics;

use Razorpay\Metrics\Processors\Processor;
use Razorpay\EC2Metadata\Ec2MetadataGetter;
use RZP\Constants\Metric;

class DimensionsProcessor implements Processor
{
    public function process(array $dimensions): array
    {
        // Modifies values in dimensions. For a list of labels only allows white-listed values or else uses default.
        // This way we ensure that labels with high cardinality are not causing issues in monitoring system and we
        // only instrument where monitoring is needed (e.g. for big merchants etc).
        $defaultLabelValue      = config('metrics.default_label_value');
        $whitelistedLabelValues = config('metrics.whitelisted_label_values');

        foreach ($whitelistedLabelValues as $label => $whitelist)
        {
            if ((array_key_exists($label, $dimensions) === true) and
                (in_array($dimensions[$label], $whitelist, true) === false))
            {
                $dimensions[$label] = $defaultLabelValue;
            }
        }

        // Adds instance tag in each metrics because our current infra setup is in such a way that we loose this label.
        // Prometheus has honor_labels configuration set to true for this. Later we will have this removed.
        $ec2 = new Ec2MetadataGetter(config('trace.cache'));

        if (config('trace.cloud') === false)
        {
            $ec2->allowDummy();
        }

        // Adds rzp_mode dimension, if doesn't exists already
        if (isset($dimensions['rzp_mode']) === false)
        {
            $dimensions['rzp_mode'] = app()['rzp.mode'] ?? 'none';
        }

        // Adds instance type label
        $dimensions['instance_type'] = env('INSTANCE_TYPE');

        // Stringify php values e.g. true -> 'true', 0 -> '0', as only unicode chars in label values is expected
        $dimensions = array_map('stringify', $dimensions);

        foreach ($dimensions as $label => $value)
        {
            if ($value === '')
            {
                $dimensions[$label] = Metric::LABEL_NONE_VALUE;
            }
        }

        return $dimensions;
    }
}
