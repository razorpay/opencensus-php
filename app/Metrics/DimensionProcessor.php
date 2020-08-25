<?php

namespace App\Metrics;

use Razorpay\Metrics\Processors\Processor;

class DimensionProcessor implements Processor
{
    public function process(array $dimensions): array
    {
        $defaultLabelValue = config('metrics.default_label_value');

        foreach ($dimensions as $label => $value)
        {
            if (empty($value) === true)
            {
                $dimensions[$label] = $defaultLabelValue;
            }
        }

        return $dimensions;
    }
}
