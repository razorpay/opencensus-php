<?php

namespace RZP\Services;

use App;

use Razorpay\Trace\Logger;
use RZP\Jobs\Kafka\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Merchant\VerificationDetail\Service;

class NegativeKeywordsConsumer extends Job
{
    /**
     * @throws \Throwable
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(TraceCode::NEGATIVE_KEYWORDS_KAFKA_CONSUME_REQUEST,  [
            'mode'          => $this->mode,
            'job_attempts'  => $this->attempts()
        ]);

        $payload = $this->getPayload();

        try
        {
            (new Service())->processNegativeKeywordsResponse($payload);
        }
        catch (\Throwable $ex)
        {
            $this->trace->traceException($ex, Logger::ERROR, TraceCode::NEGATIVE_KEYWORDS_KAFKA_CONSUME_FAILED);
        }
    }
}
