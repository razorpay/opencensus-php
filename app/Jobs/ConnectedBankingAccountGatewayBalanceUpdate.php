<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\BankingAccount;
use RZP\Services\RazorXClient;
use RZP\Models\Settlement\SlackNotification;

class ConnectedBankingAccountGatewayBalanceUpdate extends Job
{
    const MAX_RETRY_ATTEMPT = 1;

    const MAX_RETRY_DELAY = 10;

    /**
     * @var string
     */
    protected $queueConfigKey = 'connected_banking_account_gateway_balance_update';

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

            // Worker will directly delete the message based on output from gatewayBalanceUpdateDeleteMode function.
            if ($BACore->gatewayBalanceUpdateDeleteMode($this->params[BankingAccount\Entity::CHANNEL]) === true)
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_DELETE_MODE,
                    [
                        'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                        'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                    ]);
            }
            else
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_INIT,
                    [
                        'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                        'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                    ]);

                $response = (new BankingAccount\Core)->fetchAndUpdateGatewayBalanceWrapper($this->params);
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
                    'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                    'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
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
        $this->trace->count(\RZP\Jobs\Metric::RAZORPAYX_PAYOUTS_BANKING_QUEUES_TIMEOUT_COUNT, [
            'job_name'   => $this->getJobName() ?? '',
            'mode'       => $this->getMode() ?? '',
        ]);

        parent::beforeJobKillCleanUp($variant);

        $this->trace->info(TraceCode::BANKING_QUEUE_WORKER_TIMEOUT_HANDLING, [
            'is_deleted'  => optional($this->job)->isDeleted() ?? null,
            'is_released' => optional($this->job)->isReleased() ?? null,
        ]);
    }
}
