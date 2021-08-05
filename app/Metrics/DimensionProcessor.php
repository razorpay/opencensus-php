<?php

namespace App\Metrics;

use Razorpay\Metrics\Processors\Processor;

class DimensionProcessor implements Processor
{
    public function process(array $dimensions): array
    {
        $defaultLabelValue = config('metrics.default_label_value');

        // Adds instance type label e.g. production, canary, etc.
        $dimensions['instance_type'] = env('INSTANCE_TYPE');

        foreach ($dimensions as $label => $value)
        {
            if (empty($value) === true)
            {
                $dimensions[$label] = $defaultLabelValue;
            }

            if (is_string($value) !== true)
            {
                // Stringify php values e.g. true -> 'true', 0 -> '0',
                //  as only unicode chars in label values is expected
                $dimensions[$label] = json_encode($value);
            }
        }

        return $dimensions;
    }
}
