<?php

namespace RZP\Jobs;

use RZP\Trace\Tracer;
use RZP\Services\FTS;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Constants\HyperTrace;
use RZP\Models\FundAccount\Validation\Core as FAVCore;
use RZP\Models\FundAccount\Validation\Metric as FAVMetric;

class FavQueueForFTS extends Job
{
    // max number of retry attempts
    const MAX_RETRY_ATTEMPTS = 4;

    // Min delay used for exponential backoff
    // Setting it to 1 sec
    // With each retry, the release wait period will double
    // Hence, the total time spent in waiting between retries shall be (1 + 2 + 4 + 8) = 15 secs
    const MIN_RETRY_DELAY = 1;

    /**
     * @var string
     */
    protected $queueConfigKey = 'fav_queue_for_fts';

    protected $favId;

    public function __construct(string $mode, string $id)
    {
        // FAV ID
        $this->favId = $id;

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::FAV_QUEUE_FOR_FTS_JOB_INIT,
                [
                    'fav_id' => $this->favId,
                ]
            );

            /* The following function call is supposed to do the following
             * Fetch the FAV entity from the FAV ID.
             * Create the request body as per the API contract.
             * Create a new Services\FTS\Transfer\Client object, and set its $request using setRequest() method call.
             * Invoke Client object's doTransfer() method.
             * Use the response code to decide the next step (update FAV entity, or queue for retry)
             * If the response received above is a Status OK, then remove the job from the queue.
             */

            $favCore = new FAVCore();

            $response = $favCore->sendFAVRequestToFTS($this->favId);

            if (empty($response[FTS\Constants::BODY][FTS\Constants::FUND_TRANSFER_ID]) === false)
            {
                $ftsTransferId = $response[FTS\Constants::BODY][FTS\Constants::FUND_TRANSFER_ID];

                $favCore->setTransferId($this->favId, $ftsTransferId);
            }

            $this->trace->info(
                TraceCode::FAV_QUEUE_FOR_FTS_JOB_SUCCESSFUL,
                [
                    'fav_id'   => $this->favId,
                    'response' => $response,
                ]
            );

            $this->delete();

        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Logger::CRITICAL,
                TraceCode::FAV_QUEUE_FOR_FTS_JOB_FAILED,
                [
                    'fav_id'  => $this->favId,
                    'message' => $exception->getMessage(),
                ]);

            $this->trace->count(FAVMetric::FAV_QUEUE_FOR_FTS_JOB_FAILED_OR_RETRY_ATTEMPT_EXHAUSTED);

            Tracer::startSpanWithAttributes( HyperTrace::FAV_QUEUE_FOR_FTS_JOB_FAILED_OR_RETRY_ATTEMPT_EXHAUSTED);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        // TODO: Add handling for each category of exceptions during circuit breaker implementation

        $noOfAttempts = $this->attempts();

        if ($noOfAttempts < self::MAX_RETRY_ATTEMPTS)
        {
            $this->trace->info(
                TraceCode::FAV_QUEUE_FOR_FTS_JOB_RELEASED,
                [
                    'fav_id' => $this->favId,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );

            $this->release(self::MIN_RETRY_DELAY * pow(2, ($noOfAttempts - 1)));
        }
        else
        {
            // TODO: Add Sumo alert.
            $this->trace->error(
                TraceCode::FAV_QUEUE_FOR_FTS_JOB_DELETED,
                [
                    'fav_id' => $this->favId,
                    'no_of_attempts' => $noOfAttempts,
                ]
            );

            $this->trace->count(FAVMetric::FAV_QUEUE_FOR_FTS_JOB_FAILED_OR_RETRY_ATTEMPT_EXHAUSTED);

            Tracer::startSpanWithAttributes( HyperTrace::FAV_QUEUE_FOR_FTS_JOB_FAILED_OR_RETRY_ATTEMPT_EXHAUSTED);

            $this->delete();
        }
    }
}
