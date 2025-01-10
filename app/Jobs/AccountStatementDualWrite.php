<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;
use RZP\Models\BankingAccountStatement;

class AccountStatementDualWrite extends Job
{
    const MAX_RETRY_ATTEMPT = 5;

    const MAX_RETRY_DELAY = 10;

    const MAX_ATTEMPTS_FOR_DUAL_WRITE = 3;

    const ENTITY_TYPE = 'entity_type';
    const ENTITY_ID = 'entity_id';

    /**
     * @var string
     */
    protected $queueConfigKey = 'account_statement_dual_write';

    /**
     * @var array
     */
    protected $params;

    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::ACCOUNT_STATEMENT_DUAL_WRITE_INIT,
                $this->params
            );

            (new BankingAccountStatement\Core)->processAccountStatementDualWrite($this->params);

            $this->trace->info(
                TraceCode::ACCOUNT_STATEMENT_DUAL_WRITE_COMPLETE,
                $this->params);

            $this->delete();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::ACCOUNT_STATEMENT_DUAL_WRITE_FAILURE,
                $this->params);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_ATTEMPTS_FOR_DUAL_WRITE)
        {
            $this->trace->info(
                TraceCode::ACCOUNT_STATEMENT_DUAL_WRITE_JOB_RELEASE,
                $this->params);

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            $this->trace->info(
                TraceCode::ACCOUNT_STATEMENT_DUAL_WRITE_JOB_DELETE,
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
