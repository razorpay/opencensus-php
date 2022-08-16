<?php

namespace RZP\Models\Merchant\OneClickCheckout\Utils;

use App;
use RZP\Exception;
use RZP\Models\Base;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant\Metric;

/**
 * Do NOT send any entity id in the $dimensions or you will crash Prometheus
 */
class Monitoring extends Base\Core
{

    public function addTraceCount(string $metric, array $dimensions = [], $useDefaultDimensions = true): void
    {
        $defaultDimensions = $this->getDefaultDimensions($useDefaultDimensions);

        $this->trace->count(
            $metric,
            array_merge($defaultDimensions, $dimensions));
    }

    public function traceResponseTime(
        string $metric,
        int $startTime,
        array $dimensions = [],
        $useDefaultDimensions = true): void
    {
        $duration = millitime() - $startTime;

        $defaultDimensions = $this->getDefaultDimensions($useDefaultDimensions);

        $this->trace->histogram(
            $metric,
            $duration,
            array_merge($defaultDimensions, $dimensions));
    }

    // for $direct routes the mode set may be incorrect so we skip logging it
    // and rely on the webhook processor to handle things
    protected function getDefaultDimensions(bool $useDefaultDimensions): array
    {
        $defaultDimensions = [];

        if ($useDefaultDimensions === true)
        {
            $defaultDimensions = [
                'mode' => $this->mode,
            ];
        }

        return $defaultDimensions;
    }
}
