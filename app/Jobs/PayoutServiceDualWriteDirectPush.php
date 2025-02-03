<?php

namespace RZP\Jobs;

use App;
use Carbon\Carbon;

use RZP\Constants\Mode;
use RZP\Constants\Timezone;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\Payout\Core;
use RZP\Models\Payout\Entity;
use RZP\Services\RazorXClient;
use RZP\Models\BankingAccountStatement;

/*
 * Currently dual write happens in the following manner:
 * 1. Payouts pushes message to Payouts sqs
 * 2. Payouts sqs triggers HTTP call to api
 * 3. Api pushes message to API sqs
 *
 * This job is responsible for directly pushing the message to API sqs from payouts service.
 * We need a separate job because we need to handle message format in a separate format.
 */
class PayoutServiceDualWriteDirectPush extends Job
{
    const MAX_RETRY_ATTEMPT = 5;

    const MAX_RETRY_DELAY = 10;

    const MAX_ATTEMPTS_FOR_DUAL_WRITE = 3;

    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID = 'entity_id';

    /**
     * @var string
     */
    protected $queueConfigKey = 'payout_service_dual_write_direct_push';

    /**
     * @var array
     */
    protected $params;

    public function __construct(array $params)
    {
        $this->params = $params;

        parent::__construct(Mode::LIVE);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_DIRECT_PUSH_INIT,
                $this->params
            );

            if (array_key_exists("direct_push_from_ps_to_api", $this->params))
            {
                $app = App::getFacadeRoot();

                $currentTime = Carbon::now(Timezone::IST)->getTimestamp();

                $psDispatchedTime = $this->params["timestamp"];

                $timeDiff = $currentTime - $psDispatchedTime;

                $app['trace']->histogram(Metric::PAYOUT_SERVICE_DUAL_WRITE_DIRECT_PUSH_LAG, $timeDiff);

                unset($this->params["direct_push_from_ps_to_api"]);
            }

            switch ($this->params[self::ENTITY_TYPE])
            {
                case 'payout':
                    $input = $this->params;
                    $input[Entity::PAYOUT_ID] = $input[self::ENTITY_ID];
                    unset($input[self::ENTITY_TYPE]);
                    unset($input[self::ENTITY_ID]);

                    (new Core)->processDualWrite($input);
                    break;
            }

            $this->trace->info(
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_DIRECT_PUSH_COMPLETE,
                $this->params);

            $this->delete();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::PAYOUT_SERVICE_DUAL_WRITE_DIRECT_PUSH_FAILURE,
                $this->params);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_ATTEMPTS_FOR_DUAL_WRITE)
        {
            $this->trace->info(
                TraceCode::PAYOUTS_DUAL_WRITE_DIRECT_PUSH_JOB_RELEASE,
                $this->params);

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            // Update payout service meta table for dual write failure
            // which will be used by the cron to retry the dual write
            if ($this->params[self::ENTITY_TYPE] === 'payout')
            {
                (new Core)->upsertMetaDataInPayoutServiceForDualWriteRetryExhaust($this->params);
            }

            $this->trace->info(
                TraceCode::PAYOUTS_DUAL_WRITE_DIRECT_PUSH_JOB_DELETE,
                $this->params);

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
