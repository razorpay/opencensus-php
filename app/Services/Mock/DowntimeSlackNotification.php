<?php

namespace RZP\Services\Mock;

use \RZP\Services\DowntimeSlackNotification as BaseSlackNotification;
use RZP\Trace\TraceCode;

class DowntimeSlackNotification extends BaseSlackNotification {

    public function notifyPaymentDowntime($downtime): void{
        $this->app['trace']->info(TraceCode::SLACK_MOCK_SERVICE, []);
    }

}
