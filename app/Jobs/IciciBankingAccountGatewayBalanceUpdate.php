<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\BankingAccount;
use RZP\Services\RazorXClient;
use RZP\Models\BankingAccount\Metrics;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement\Core as BASCore;

class IciciBankingAccountGatewayBalanceUpdate extends Job
{
    //TODO: move constants in config
    const MAX_RETRY_ATTEMPT = 1;

    const MAX_RETRY_DELAY = 10;

    /**
     * @var string
     */
    protected $queueConfigKey = 'icici_banking_account_gateway_balance_update';

    // config for priority balance update queue
    public const PRIORITY_QUEUE_CONFIG_KEY = 'icici_banking_account_gateway_balance_priority_update';

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

            $BACore = new BankingAccount\Core;

            $isHighPriorityBalanceUpdate = false;

            if (str_contains($this->queue, 'high-priority'))
            {
                $isHighPriorityBalanceUpdate = true;
            }

            // Worker will directly delete the message based on output from gatewayBalanceUpdateDeleteMode function.
            if ($BACore->gatewayBalanceUpdateDeleteMode($this->params[BankingAccount\Entity::CHANNEL]) === true)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE,
                    [
                        'channel'          => $this->params[BankingAccount\Entity::CHANNEL],
                        'merchant_id'      => $this->params[BankingAccount\Entity::MERCHANT_ID],
                        'queue_name'       => $this->queue,
                        'is_high_priority' => $isHighPriorityBalanceUpdate
                    ]);
            }
            else
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_INIT,
                    [
                        'channel'          => $this->params[BankingAccount\Entity::CHANNEL],
                        'merchant_id'      => $this->params[BankingAccount\Entity::MERCHANT_ID],
                        'queue_name'       => $this->queue,
                        'is_high_priority' => $isHighPriorityBalanceUpdate
                    ]);

                $BASCore = new BASCore();

                if ($BASCore->shouldBlockNon2faAndNonBaasMerchants($this->params) === true)
                {
                    $this->delete();

                    return;
                }

                if ($isHighPriorityBalanceUpdate == true)
                {
                    $this->trace->count(Metrics::BANKING_ACCOUNT_PRIORITY_GATEWAY_BALANCE_INIT, [
                        'channel' => $this->params[BankingAccount\Entity::CHANNEL]
                    ]);
                }

                $this->trace->count(Metrics::BANKING_ACCOUNT_GATEWAY_BALANCE_INIT, [
                    'channel'            => $this->params[BankingAccount\Entity::CHANNEL],
                    'is_priority_update' => $isHighPriorityBalanceUpdate
                ]);

                $response = (new BankingAccount\Core)->fetchAndUpdateGatewayBalanceWrapper($this->params, $isHighPriorityBalanceUpdate);
            }

            $this->delete();
        }
        catch (\Throwable $exception)
        {
            $this->trace->traceException(
                $exception,
                TraceCode::ERROR_EXCEPTION,
                TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_FAILED,
                [
                    'channel'          => $this->params[BankingAccount\Entity::CHANNEL],
                    'merchant_id'      => $this->params[BankingAccount\Entity::MERCHANT_ID],
                    'queue_name'       => $this->queue,
                    'is_high_priority' => $isHighPriorityBalanceUpdate
                ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_RELEASED,
                               [
                                   'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                                   'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                                   'queue_name'  => $this->queue,
                               ]);

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            $this->trace->error(TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_DELETED,
                                [
                                    'channel'      => $this->params[BankingAccount\Entity::CHANNEL],
                                    'merchant_id'  => $this->params[BankingAccount\Entity::MERCHANT_ID],
                                    'job_attempts' => $this->attempts(),
                                    'queue_name'   => $this->queue,
                                ]);

            $this->trace->count(BankingAccount\Metrics::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_FAILED, [
                Metric::LABEL_RZP_MERCHANT_ID   => $this->params[BankingAccount\Entity::MERCHANT_ID],
                Metric::LABEL_TRACE_CHANNEL     => $this->params[BankingAccount\Entity::CHANNEL],
            ]);

            $this->delete();
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
            'channel'      => $this->params[BankingAccount\Entity::CHANNEL],
            'merchant_id'  => $this->params[BankingAccount\Entity::MERCHANT_ID],
        ];

        $this->handleWorkerTimeoutGracefully($context, self::MAX_RETRY_ATTEMPT, self::MAX_RETRY_DELAY);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
            'queue_name'  => $this->queue,
        ]);
    }
}
