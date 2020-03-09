<?php

namespace RZP\Jobs;

use RZP\Trace\TraceCode;
use RZP\Models\BankingAccount;
use RZP\Models\Settlement\SlackNotification;

class BankingAccountGatewayBalanceUpdate extends Job
{
    //TODO: move constants in config
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 10;

    /**
     * @var string
     */
    //TODO:// set queueConfigKey using channel name in a constructor when integrate with more banks with CA
    protected $queueConfigKey = 'rbl_banking_account_gateway_balance_update';

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
                TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_INIT,
                [
                    'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                    'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                ]);

            $response = (new BankingAccount\Core)->fetchAndUpdateGatewayBalance($this->params);

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
            $this->release(self::MAX_RETRY_DELAY);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_RELEASED,
                               [
                                   'channel'     => $this->params[BankingAccount\Entity::CHANNEL],
                                   'merchant_id' => $this->params[BankingAccount\Entity::MERCHANT_ID],
                               ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::BANKING_ACCOUNT_GATEWAY_BALANCE_UPDATE_JOB_DELETED,
                                [
                                    'channel'      => $this->params[BankingAccount\Entity::CHANNEL],
                                    'merchant_id'  => $this->params[BankingAccount\Entity::MERCHANT_ID],
                                    'job_attempts' => $this->attempts(),
                                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                                ]);

            $operation = 'banking account gateway balance update job failed';

            (new SlackNotification)->send($operation, $this->params, null, 1, 'rbl_alerts');
        }
    }
}
