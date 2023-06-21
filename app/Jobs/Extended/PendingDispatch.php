<?php

namespace RZP\Jobs\Extended;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;

/**
 * Overridden: Just before destruction, sets proper connection and queue name.
 *
 * Additional api specific feature:
 * Routes a job to specific queue basis few additional arguments.
 *
 * E.g.
 * \RZP\Jobs\Webhook::dispatch($data)->using(['payment.authorized']): Will dispatch the job over queue connection &
 * queue name as defined in config/queue.php. It will look for webhook.connection to pick the queue connection &
 * webhook.payment.authorized for queue name.
 */
class PendingDispatch extends \Illuminate\Foundation\Bus\PendingDispatch
{
    /**
     * @var null|string
     */
    protected $queueConfigKey;

    /**
     * @var array
     */
    protected $queueConfigExtra = [];

    /**
     * Metric constants
     */
    const JOB_DISPATCH_FAILED   = 'job_dispatch_failed';

    // Queue config keys to throw error if any.

    const PAYOUT_POST_CREATE_PROCESS                  = 'payout_post_create_process';
    const PAYOUT_POST_CREATE_PROCESS_LOW_PRIORITY     = 'payout_post_create_process_low_priority';
    const PAYOUT_SERVICE_DUAL_WRITE                   = 'payout_service_dual_write';
    const QUEUED_CREDIT_TRANSFER_REQUESTS             = 'queued_credit_transfer_requests';
    const BANKING_ACCOUNT_STATEMENT_UPDATE            = 'banking_account_statement_update';
    const BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO = 'banking_account_statement_recon_process_neo';
    const BANKING_ACCOUNT_STATEMENT_RECON_NEO         = 'banking_account_statement_recon_neo';
    const BANKING_ACCOUNT_STATEMENT_RECON             = 'banking_account_statement_recon';
    const MISSING_ACCOUNT_STATEMENT_DETECT            = 'missing_account_statement_detect';
    const MISSING_ACCOUNT_STATEMENT_RECON             = 'banking_account_statement_recon';
    const BANKING_ACCOUNT_STATEMENT_SOURCE_LINKING    = 'banking_account_statement_source_linking';
    const FUND_MANAGEMENT_PAYOUT_CHECK                = 'fund_management_payout_check';
    const FUND_MANAGEMENT_PAYOUT_INITIATE             = 'fund_management_payout_initiate';

    protected $shouldThrowErrorOnFailure = [
        self::PAYOUT_POST_CREATE_PROCESS,
        self::PAYOUT_POST_CREATE_PROCESS_LOW_PRIORITY,
        self::PAYOUT_SERVICE_DUAL_WRITE,
        self::QUEUED_CREDIT_TRANSFER_REQUESTS,
        self::BANKING_ACCOUNT_STATEMENT_UPDATE,
        self::BANKING_ACCOUNT_STATEMENT_RECON,
        self::BANKING_ACCOUNT_STATEMENT_RECON_NEO,
        self::BANKING_ACCOUNT_STATEMENT_RECON_PROCESS_NEO,
        self::MISSING_ACCOUNT_STATEMENT_DETECT,
        self::MISSING_ACCOUNT_STATEMENT_RECON,
        self::BANKING_ACCOUNT_STATEMENT_SOURCE_LINKING,
        self::FUND_MANAGEMENT_PAYOUT_CHECK,
        self::FUND_MANAGEMENT_PAYOUT_INITIATE,
    ];

    /**
     * Overrides
     * {@inheritDoc}
     */
    public function __destruct()
    {
        try
        {
            $this->setQueueAndConnectionFromConfig();

            parent::__destruct();
        }
        catch (\Throwable $e)
        {
            $traceData = [
                'command_name'  => get_class($this->job),
                'command'       => is_object($this->job) ? serialize(clone $this->job) : $this->job,
            ];

            app('trace')->traceException($e, Trace::CRITICAL, TraceCode::QUEUE_DISPATCH_JOB_FAILURE, $traceData);

            // Throwing error in case of failures in successfully dispatching message to queue. Normally exceptions caught here are not being thrown.
            if (($this->throwErrorOnFailure() === true) and
                ($e->getMessage() === "Target class [rzp.mode] does not exist."))
            {
                app('trace')->info(TraceCode::QUEUE_DISPATCH_JOB_FAILURE, ['queue_name' => $this->queueConfigKey, 'error' =>  "Dispatching Error"]);

                throw $e;
            }


            $tokens = explode('\\', get_class($this->job));

            $job    = $tokens[count($tokens) - 1];

            $dimensions = [
                'async_job_name' => $job,
                'queue_name'     => $this->getQueue(),
                'mode'           => app('rzp.mode'),
            ];

            app('trace')->count(self::JOB_DISPATCH_FAILED, $dimensions);

            // Throwing error in case of failures in successfully dispatching message to queue. Normally exceptions caught here are not being thrown.
            if ($this->throwErrorOnFailure() === true)
            {
                app('trace')->info(TraceCode::QUEUE_DISPATCH_JOB_FAILURE, ['queue_name' => $this->queueConfigKey, 'error' =>  "Dispatching Error"]);
                throw $e;
            }
        }
    }

    /**
     * @param  array       $extra - This is first argument as it is almost the most number of use case
     * @param  string|null $key
     * @return PendingDispatch
     */
    public function using(array $extra = [], string $key = null): PendingDispatch
    {
        $this->queueConfigExtra = $extra;
        $this->queueConfigKey   = $key;

        return $this;
    }

    /**
     * Sets job's queue connection and queue name as per configuration
     */
    protected function setQueueAndConnectionFromConfig()
    {
        // If queue routing is mocked, push everything to default queue; Used in local environment;
        $queueRouteMock = (bool) config('queue.routing_mock', false);
        $this->queueConfigKey = $this->queueConfigKey ?: $this->job->getQueueConfigKey();

        if (($queueRouteMock === true) or (empty($this->queueConfigKey) === true))
        {
            return;
        }

        $this->job->onConnection($this->getConnection());

        $this->job->onQueue($this->getQueue());
    }

    protected function getConnection(): string
    {
        $key     = "queue.{$this->queueConfigKey}.connection";
        $default = config('queue.default');

        return config($key, $default);
    }

    protected function getQueue(): string
    {
        $mode = app('rzp.mode');
        $key  = "queue.{$this->queueConfigKey}.{$mode}";

        // We flatten with the extra parameter to finally get the queue name.
        // Ref: config/queue.php
        array_unshift($this->queueConfigExtra, $key);
        $key = implode('.', $this->queueConfigExtra);

        $default = config('queue.connections.sqs.queue');

        return config($key, $default);
    }

    protected function throwErrorOnFailure()
    {
        return in_array(strtolower($this->queueConfigKey), $this->shouldThrowErrorOnFailure, true);
    }
}
