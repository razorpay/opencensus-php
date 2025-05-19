<?php

namespace RZP\Jobs;

use RZP\Constants\Mode;
use Razorpay\Trace\Logger as Trace;

use RZP\Exception\BadRequestValidationFailureException;
use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Services\RazorXClient;
use RZP\Models\BankingAccountStatement;
use Throwable;

class XBalanceDualWrite extends Job
{
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 10;

    const MAX_ATTEMPTS_FOR_DUAL_WRITE = 3;

    /**
     * @var string
     */
    protected $queueConfigKey = 'x_balance_dual_write';

    /**
     * @var array
     */
    protected $params;

    public function __construct(array $payload)
    {
        $this->params = $payload;

        parent::__construct(Mode::LIVE);
    }

    /**
     * @throws BadRequestValidationFailureException
     * @throws Throwable
     */
    public function handle()
    {
        parent::handle();

        $this->trace->info(
            TraceCode::X_BALANCE_DUAL_WRITE_INIT,
            $this->params
        );

        try
        {
            $this->createCore()->handleDualWrite($this->params);
        }
        catch (Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::X_BALANCE_DUAL_WRITE_FAILURE,
                $this->params);

            $this->checkRetry();
            throw $e;
        }

        $this->trace->info(
            TraceCode::X_BALANCE_DUAL_WRITE_COMPLETE,
            $this->params);

        $this->delete();
    }

    /**
     * Create Core instance - extracted for testing
     */
    protected function createCore(): BankingAccountStatement\Details\Core
    {
        return new BankingAccountStatement\Details\Core();
    }

    protected function checkRetry(): void
    {
        if ($this->attempts() < self::MAX_ATTEMPTS_FOR_DUAL_WRITE)
        {
            $this->trace->info(
                TraceCode::X_BALANCE_DUAL_WRITE_JOB_RELEASE,
                $this->params);

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            $this->trace->info(
                TraceCode::X_BALANCE_DUAL_WRITE_JOB_DELETE,
                $this->params);

            $this->delete();
        }
    }

    /**
     * Defines how the job is handled in an event of worker timeout
     */
    protected function beforeJobKillCleanUp($variant = RazorXClient::DEFAULT_CASE): void
    {
        $this->trace->count(Metric::RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT, [
            'job_name'   => $this->getJobName() ?? '',
            'mode'       => $this->getMode() ?? '',
        ]);

        parent::beforeJobKillCleanUp($variant);

        $this->handleWorkerTimeoutGracefully();

        $this->trace->info(TraceCode::X_BALANCE_DUAL_WRITE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }

    protected function handleWorkerTimeoutGracefully($context = [], $maxRetries = 1, $retryDelay = 0): void
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
}
