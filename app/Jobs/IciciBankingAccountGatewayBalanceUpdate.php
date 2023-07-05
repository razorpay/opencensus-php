<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Constants\Metric;
use RZP\Models\BankingAccount;
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

                $BASCore = new BASCore();

                if ($BASCore->shouldBlockNon2faAndNonBaasMerchants($this->params) === true)
                {
                    $this->delete();

                    return;
                }

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

            $operation = 'banking account gateway balance update job failed';

            // TODO:// change channel for alerts if wanted to have separately for icici
            (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_ca_rbl_alerts');

            $this->delete();
        }
    }
}
