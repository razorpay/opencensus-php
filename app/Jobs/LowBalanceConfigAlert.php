<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\Merchant\Balance\LowBalanceConfig;

class LowBalanceConfigAlert extends Job
{
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 10;

    /**
     * @var string
     */

    protected $queueConfigKey = 'low_balance_config_alerts';

    /**
     * @var array
     */
    protected $params;

    public function __construct(string $mode, array $params)
    {
        // these are low_balance_config ids
        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(
                TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_INIT,
                [
                    'params' => $this->params,
                ]);

            [$balanceIdsForNotification,
                $balanceIdsForAutoloadBalance] = (new LowBalanceConfig\Core)->checkLowBalanceConfigsForAlert($this->params);

            $this->trace->info(
                TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_COMPLETE,
                [
                    'notification_balance_ids'  => $balanceIdsForNotification,
                    'autoload_balance_ids'      => $balanceIdsForAutoloadBalance,
                ]);

            $this->delete();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                Trace::ERROR,
                TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_FAILED,
                [
                    'params'  => $this->params,
                    'message' => $exception->getMessage(),
                ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->info(
                TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_RELEASED,
                [
                   'params' => $this->params,
                ]
            );

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            $this->trace->error(
                TraceCode::LOW_BALANCE_CONFIG_ALERTS_JOB_DELETED,
                [
                    'params'       => $this->params,
                    'job_attempts' => $this->attempts(),
                ]
            );

            $this->delete();
        }
    }
}
