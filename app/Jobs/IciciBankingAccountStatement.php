<?php

namespace RZP\Jobs;

use Carbon\Carbon;
use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement as BAS;

class IciciBankingAccountStatement extends Job
{
    //TODO: Move these constants to config
    const MAX_RETRY_ATTEMPT = 7;

    const MAX_RETRY_DELAY = 120;

    /**
     * @var string
     */
    protected $queueConfigKey = 'icici_banking_account_statement_fetch';

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

            $this->trace->info(
                TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_INIT,
                [
                    'channel'           => $this->params['channel'],
                    'account_number'    => $this->params['account_number']
                ]);

            $workerStartTime = Carbon::now()->getTimestamp();

            $result = (new BAS\Core)->processStatementForAccount($this->params);

            $workerEndTime = Carbon::now()->getTimestamp();

            $this->trace->info(TraceCode::BAS_FETCH_PROCESSED_BY_QUEUE,
                [
                    'result'        => $result,
                    'start_time'    => $workerStartTime,
                    'end_time'      => $workerEndTime
                ]);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED, [
                'channel'           => $this->params['channel'],
                'account_number'    => $this->params['account_number']
            ]);

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() < self::MAX_RETRY_ATTEMPT)
        {
            $workerRetryDelay = self::MAX_RETRY_DELAY * pow(2, $this->attempts());

            $this->release($workerRetryDelay);

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_RELEASED, [
                'channel'               => $this->params['channel'],
                'account_number'        => $this->params['account_number'],
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ]);
        }
        else
        {
            $this->delete();

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, [
                'channel'           => $this->params['channel'],
                'account_number'    => $this->params['account_number'],
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $operation = 'icici banking account statement fetch job failed';

            //TODO:// setup new channel for icici
            (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_ca_rbl_alerts');
        }
    }
}
