<?php

namespace RZP\Services;

use Throwable;
use RZP\Jobs\Kafka\Job;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Merchant\VerificationDetail\Service;


class MccCategorisationConsumer extends Job
{
    /**
     * @throws Throwable
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::MCC_CATEGORISATION_KAFKA_CONSUME_REQUEST,  [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode
        ]);

        $payload = $this->getPayload();

        try
        {
            (new Service())->processMccCategorisationResponse($payload);
        }
        catch(\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::MCC_CATEGORISATION_KAFKA_CONSUME_FAILED);
        }
    }
}
/*
 * */
