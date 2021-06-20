<?php

namespace RZP\Models\P2p\Base\Metrics;

use Razorpay\Trace\Logger;

class Metric
{
    protected function count(string $metricName, array $dimensions, int $count = 1)
    {
        $this->trace()->count($metricName, $dimensions, $count);
    }

    protected function histogram(string $metricName, array $dimensions, float $value)
    {
        $this->trace()->histogram($metricName, $value, $dimensions);
    }

    private function trace(): Logger
    {
        return app('trace');
    }
}
