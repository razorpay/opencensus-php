<?php

namespace RZP\Jobs;

use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement as BAS;

class BankingAccountStatementProcessor extends Job
{
    //TODO: Move these constants to config
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 120;

    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement_processor';

    /**
     * @var array
     */
    protected $params;

    /**
     * Default timeout value for a job is 60s. Changing it to 300s
     * as account statement process takes 1-2 mins to complete.
     * @var integer
     */
    public $timeout = 1800;

    /**
     * @param string $mode
     * @param array  $params
     *      1. channel (bank channel)
     *      2. Account number (account for statement fetch)
     */
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

            $BASCore = new BAS\Core;

            $BASCore->getBasDetails($this->params['account_number'], $this->params['channel']);

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESSOR_JOB_INIT,
                [
                    BAS\Entity::CHANNEL            => $this->params['channel'],
                    BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                    BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                ]);

            $workerStartTime = Carbon::now()->getTimestamp();

            $BASCore->processStatementForAccountV2($this->params);

            $workerEndTime = Carbon::now()->getTimestamp();

            $this->trace->info(TraceCode::BAS_PROCESSED_BY_QUEUE,
                [
                    BAS\Entity::CHANNEL            => $this->params['channel'],
                    BAS\Entity::MERCHANT_ID        => $BASCore->getBasDetails()->getMerchantId(),
                    BAS\Details\Entity::BALANCE_ID => $BASCore->getBasDetails()->getBalanceId(),
                    'start_time'                   => $workerStartTime,
                    'end_time'                     => $workerEndTime,
                ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESSOR_JOB_FAILED, $this->params);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $data                           = $this->params;
            $data[BAS\Core::DELAY]          = $workerRetryDelay;
            $data[BAS\Core::ATTEMPT_NUMBER] = 1 + $this->attempts();

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESSOR_JOB_RELEASED, $data);

            $this->release($workerRetryDelay);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_PROCESSOR_JOB_DELETED,
                                $this->params +
                                [
                                    'job_attempts' => $this->attempts(),
                                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                                ]);

            $operation = 'banking account statement processor job failed';

            (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_ca_rbl_alerts');
        }
    }
}
