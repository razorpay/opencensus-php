<?php

namespace RZP\Services\EzetapNotification;

use RZP\Models\Base;

class EzetapNotificationMetric extends Base\Core
{
    const EZETAP_NOTIFICATION_SUCCESS                    = 'ezetap_notification_success';
    const EZETAP_NOTIFICATION_FAILED                     = 'ezetap_notification_failed';
    const EZETAP_NOTIFICATION_LATENCY                    = 'ezetap_notification_latency';

    const LABEL_ERROR_MESSAGE = 'error_message';
    const LABEL_EVENT_NAME = 'event_name';


    public function pushEzetapNotificationMetrics($input, $errorMessage)
    {

        $dimensions = [
            self::LABEL_ERROR_MESSAGE => ($errorMessage === null) ? $errorMessage : substr($errorMessage, 0, 100),
            self::LABEL_EVENT_NAME    => $input['event'],
        ];

        $metric = self::EZETAP_NOTIFICATION_SUCCESS;

        if ($errorMessage != null)
        {
            $metric = self::EZETAP_NOTIFICATION_FAILED;
        }

        $this->trace->count(
            $metric,
            $dimensions
        );
    }

    public function pushEzetapNotificationLatencyMetrics($input, $startTimeMs)
    {
        $processingTimeMs = (microtime(true) * 1000) - $startTimeMs;

        $dimensions = [
            self::LABEL_EVENT_NAME => $input['event'],
        ];

        $this->trace->histogram(self::EZETAP_NOTIFICATION_LATENCY, $processingTimeMs, $dimensions);
    }
}
