<?php

namespace RZP\Jobs;

use App;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;
use RZP\Models\FundAccount\DetailsPropagator\Core as DetailsPropagator;

/***
 * Class FundAccountDetailsPropagatorJob
 * @package RZP\Jobs
 */
class FundAccountDetailsPropagatorJob extends Job
{

    const MAX_RETRIES = 3;

    const MAX_RETRY_DELAY = 300;

    protected $queueConfigKey = 'fund_account_details_propagator';

    protected $fundAccountId;

    public function __construct(string $mode, string $fundAccountId)
    {
        parent::__construct($mode);

        $this->fundAccountId = $fundAccountId;
    }

    public function handle()
    {
        parent::handle();

        $context = [
            DetailsPropagator::FUND_ACCOUNT_ID  => $this->fundAccountId
        ];

        try
        {
            $fundAccount = $this->repoManager
                ->fund_account
                ->findByPublicId($this->fundAccountId);

            $this->trace->info(
                TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB,
                $context
            );

            DetailsPropagator::update($fundAccount, $this->mode);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB_FAILED,
                $context
            );

            if ($this->attempts() < self::MAX_RETRIES)
            {
                $this->trace->info(TraceCode::FUND_ACCOUNT_DETAILS_PROPAGATOR_JOB_RELEASE,
                    $context);

                $this->release(self::MAX_RETRY_DELAY);
            }
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

        $context = [
            'fund_account_id' => $this->fundAccountId
        ];

        $this->handleWorkerTimeoutGracefully($context, self::MAX_RETRIES, self::MAX_RETRY_DELAY);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
