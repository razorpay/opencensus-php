<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger;

use RZP\Constants\Mode;
use RZP\Trace\Tracer;
use RZP\Services\FTS;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Constants\HyperTrace;
use RZP\Services\RazorXClient;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\FundAccount\Validation\Entity;
use RZP\Models\FundAccount\Validation\Core as FAVCore;
use RZP\Models\FundAccount\Entity as FundAccountEntity;
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

    protected $favInput;

    public function __construct(array $payload)
    {
        // FAV ID
        $payload['id'] = Entity::stripDefaultSign($payload['id']);

        $this->favId = $payload['id'];

        $this->favInput = $payload;

        parent::__construct($payload['mode'] ?? Mode::LIVE);
    }

    public function handle()
    {
        $favCore = new FAVCore();

        $startTime = microtime(true);

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

            $response = $favCore->sendFAVRequestToFTS($this->favId, $this->favInput);

            if (empty($response[FTS\Constants::BODY][FTS\Constants::FUND_TRANSFER_ID]) === false)
            {
                $ftsTransferId = $response[FTS\Constants::BODY][FTS\Constants::FUND_TRANSFER_ID];

                if (empty($this->favInput['is_validx']) === false and
                    $this->favInput['is_validx'] === true)
                {
                    $data = $response[FTS\Constants::BODY];

                    $favCore->forwardBankWebhookToFavService($data, 'fts');
                }
                else
                {
                    $favCore->setTransferId($this->favId, $ftsTransferId);
                }
            }

            $this->trace->info(
                TraceCode::FAV_QUEUE_FOR_FTS_JOB_SUCCESSFUL,
                [
                    'fav_id'   => $this->favId,
                    'response' => $response,
                    'worker_total_time' => (microtime(true) - $startTime) * 1000
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
                    'worker_total_time' => (microtime(true) - $startTime) * 1000
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

            (new SlackNotification)->send(
                'Could not validate Fund Account. All Penny drop attempts exhausted',
                [
                    'fav_id' => $this->favId,
                ],
                null,
                $noOfAttempts,
                'x-payouts-apps-and-txn-alerts');

            $this->delete();
        }
    }

    protected function handleWorkerTimeoutGracefully($context = [], $maxRetries = 1, $retryDelay = 0)
    {
        $internalJob = $this->job;

        $jobIsDeletedOrReleased = false;

        /**
         * Generally all Internal Jobs extends Illuminate\Contracts\Queue\Job interface, which
         * means isDeletedOrReleased() method will always exist. Adding this as an additional safety check.
         */
        if ((is_null($internalJob) === false) and
            (method_exists($internalJob, 'isDeletedOrReleased')))
        {
            $jobIsDeletedOrReleased = $internalJob->isDeletedOrReleased();
        }

        if ($jobIsDeletedOrReleased === false)
        {
            $this->checkRetry();
        }
    }

    /**
     * Defines how the job is handled in an event of worker timeout
     */
    protected function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE)
    {
        $this->trace->count(Metric::RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT, [
            'job_name'   => $this->getJobName() ?? '',
            'mode'       => $this->getMode() ?? '',
        ]);

        parent::beforeJobKillCleanUp($variant);

        $this->handleWorkerTimeoutGracefully();

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
