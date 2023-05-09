<?php

namespace RZP\Services;

use App;
use RZP\Jobs\Kafka\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\Detail\Service as MerchantDetailService;
class MerchantPaymentsEnabledCallbackConsumer extends Job
{

    /**
     * @throws /Throwable
     */
    public function handle()
    {
        parent::handle();

        $payload = $this->getPayload();

        $this->trace->info(TraceCode::MERCHANT_PAYMENTS_ENABLED_CALLBACK_KAFKA_REQUEST, [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'payload'      => $payload
        ]);

        (new MerchantDetailService())->storeTerminalProcurementBannerStatus($payload);
    }
}
