<?php

namespace RZP\Models\Merchant\OneClickCheckout;

use RZP\Models\Base;

class Monitoring extends Base\Core
{

    public function addTraceCount(string $metric, array $dimensions = []): void
    {
        $baseDimensions = [
            'mode' => $this->mode,
        ];

        $this->trace->count(
            $metric,
            array_merge($baseDimensions, $dimensions));
    }

    public function traceResponseTime(string $metric, int $duration, array $dimensions = []): void
    {
        $baseDimensions = [
            'mode' => $this->mode,
        ];

        $this->trace->histogram(
            $metric,
            $duration,
            array_merge($baseDimensions, $dimensions));
    }
}
