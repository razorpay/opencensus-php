<?php

namespace RZP\Services;

use App;

use RZP\Jobs\Kafka\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\BusinessDetail\Service;
use RZP\Models\Merchant\Detail\Core as MerchantDetailCore;

class WebsiteUpdateProcessor extends Job
{
    /**
     * @throws \Throwable
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::WHATCMS_KAFKA_CONSUME_REQUEST,  [
            'job_attempts' => $this->attempts(),
            'mode' => $this->mode
        ]);

        $payload = $this->getPayload();

        (new Service())->checkForPlugin($payload['merchant_id'], $payload['website_url']);

        $merchantDetailCore = (new MerchantDetailCore());

        $merchantDetailCore->generateLeadScoreForMerchant($payload['merchant_id'],
                                                          false, true);
    }
}
