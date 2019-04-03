<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\Payment\Downtime;

class PaymentDowntime extends Job
{
    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::PAYMENT_DOWNTIME_CREATE_JOB);

        (new Downtime\Core)->createFromGatewayDowntimes();
    }
}
