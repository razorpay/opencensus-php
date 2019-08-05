<?php

namespace RZP\Services;

use RZP\Models\Base;

class DowntimeMetric extends Base\Core
{
    protected $metrics = [];

    const Success = 'SUCCESS';
    const Failure = 'FAILURE';
    const NoError = 'NO_ERROR';

    public function setMetrics($gateway, $status, $errorCode = self::NoError)
    {
        if (isset($this->metrics[$gateway][$status][$errorCode])  === false)
        {
            return $this->metrics = array_merge_recursive($this->metrics, [
                        $gateway => [
                            $status => [
                                $errorCode => 1
                            ],
                        ]
                    ]);
        }

        return $this->metrics[$gateway][$status][$errorCode]++;
    }

    public function getMetrics()
    {
        return $this->metrics;
    }
}