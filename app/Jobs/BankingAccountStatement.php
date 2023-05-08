<?php

namespace RZP\Jobs;

use Carbon\Carbon;

use RZP\Models\Admin;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Settlement\SlackNotification;
use RZP\Models\BankingAccountStatement as BAS;
use RZP\Models\BankingAccountStatement\Details as BASD;

class BankingAccountStatement extends Job
{
    //TODO: Move these constants to config
    const MAX_RETRY_ATTEMPT = 7;

    const MAX_RETRY_DELAY = 120;

    /**
     * @var string
     */
    //TODO: set queueConfigKey using channel name in a constructor
    protected $queueConfigKey = 'rbl_banking_account_statement';

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
        unset($params['attempt_number']);

        $this->params = $params;

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $BASCore = new BAS\Core;

            $basDetails = $BASCore->getBasDetails($this->params['account_number'], $this->params['channel']);

            if (isset($basDetails) === false)
            {
                $this->trace->info(TraceCode::BAS_DETAILS_NOT_FOUND);

                $this->delete();
            }
            else
            {
                $this->trace->info(
                    TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_INIT,
                    [
                        'channel'        => $BASCore->getBasDetails()->getChannel(),
                        'balance_id'     => $BASCore->getBasDetails()->getBalanceId(),
                        'bas_details_id' => $BASCore->getBasDetails()->getId()
                    ]);

                $workerStartTime = Carbon::now()->getTimestamp();

                if ($BASCore->getBasDetails()->getAccountType() === BAS\Details\AccountType::SHARED)
                {
                    switch ($BASCore->getBasDetails()->getChannel())
                    {
                        case 'rbl':
                            $BasPoolCore = new BAS\Pool\Rbl\Core();
                            break;

                        case 'icici':
                            $BasPoolCore = new BAS\Pool\Icici\Core();
                    }

                    $BasPoolCore->basDetails = $BASCore->getBasDetails();

                    $result = $BasPoolCore->fetchAccountStatementV2($this->params);
                }
                else
                {
                    $result = $BASCore->processStatementForAccount($this->params);
                }

                $workerEndTime = Carbon::now()->getTimestamp();

                $this->trace->info(TraceCode::BAS_FETCH_PROCESSED_BY_QUEUE,
                                   [
                                       'result'     => $result,
                                       'start_time' => $workerStartTime,
                                       'end_time'   => $workerEndTime
                                   ]);

                $this->delete();
            }
        }
        catch (\Throwable $e)
        {
            $data = ['channel' => $this->params['channel']];

            if (array_key_exists('balance_id', $this->params) === true)
            {
                $data['balance_id'] = $this->params['balance_id'];
            }
            else
            {
                $data['account_number'] = $this->params['account_number'];
            }

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED, $data);

            $this->trace->count(BAS\Metric::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED,
                                [
                                    'channel' => $this->params['channel']
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

            $data = [
                'channel'               => $this->params['channel'],
                'attempt_number'        => 1 + $this->attempts(),
                'worker_retry_delay'    => $workerRetryDelay
            ];

            if (array_key_exists('balance_id', $this->params) === true)
            {
                $data['balance_id'] = $this->params['balance_id'];
            }
            else
            {
                $data['account_number'] = $this->params['account_number'];
            }

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_RELEASED, $data);
        }
        else
        {
            $this->delete();

            $data = [
                'channel'           => $this->params['channel'],
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ];

            if (array_key_exists('balance_id', $this->params) === true)
            {
                $data['balance_id'] = $this->params['balance_id'];
            }
            else
            {
                $data['account_number'] = $this->params['account_number'];
            }

            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, $data);

            $operation = 'banking account statement fetch job failed';

            (new SlackNotification)->send($operation, $this->params, null, 1, 'rx_ca_rbl_alerts');
        }
    }

    /**
     * Setting queue config key based on bank channel. Specific channel accounts will be pushed to dedicated queues.
     * Not using this function in current release, will need to use it in constructor when we enable statement fetch
     * for different channels
     *
     * @param $channel
     */
    protected function setQueueConfigKeyForChannelType(string $channel = null)
    {
        $configKey = $channel . '_account_statement';

        $app = App::getFacadeRoot();

        if (isset($app['config']['queue'][$configKey]) === true)
        {
            $this->queueConfigKey = $configKey;
        }
    }

}
