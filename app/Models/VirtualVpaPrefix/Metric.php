<?php


namespace RZP\Models\VirtualVpaPrefix;

use RZP\Models\Base;

class Metric extends Base\Core
{
    use Base\Traits\MetricTrait;

    const VIRTUAL_VPA_PREFIX_SUCCESS         = 'virtual_vpa_prefix_success';
    const VIRTUAL_VPA_PREFIX_FAILED          = 'virtual_vpa_prefix_failed';

    public function pushSuccessMetrics(string $action)
    {
        $dimensions = [
            'action'    => $action,
        ];

        $this->trace->count(self::VIRTUAL_VPA_PREFIX_SUCCESS, $dimensions);
    }

    public function pushFailedMetrics(string $action, \Exception $ex)
    {
        $dimensions = [
            'action'    => $action,
        ];

        $this->pushExceptionMetrics($ex, self::VIRTUAL_VPA_PREFIX_FAILED, $dimensions);
    }
}
