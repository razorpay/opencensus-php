<?php

namespace RZP\Services;

use App;

use RZP\Jobs\Kafka\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\VerificationDetail\Service;


class WebsitePolicyConsumer extends Job
{
    /**
     * @throws \Throwable
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::WEBSITE_POLICY_KAFKA_CONSUME_REQUEST,  [
            'job_attempts'  => $this->attempts(),
            'mode'          => $this->mode
        ]);

        $payload = $this->getPayload();

        try
        {
            (new Service())->processWebsitePolicyResponse($payload);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::WEBSITE_POLICY_KAFKA_CONSUME_FAILED);
        }
    }
}
