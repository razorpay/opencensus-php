<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\BankingAccountStatement as BAS;

class BankingAccountStatement extends Job
{
    const MAX_RETRY_ATTEMPT = 3;

    const MAX_RETRY_DELAY = 60;

    /**
     * @var string
     */
    protected $queueConfigKey = 'banking_account_statement';

    /**
     * @var array
     */
    protected $params;

    /**
     *  @var BAS\Core
     */
    protected $basCore;


    /**
     * @param string $mode
     * @param array  $params
     *      1. channel (bank channel)
     *      2. Account number (account for statement fetch)
     */
    public function __construct(string $mode, array $params)
    {
        $this->params = $params;

        $this->basCore = new BAS\Core;

        //$this->setQueueConfigKeyForChannelType($this->params['channel']);

        parent::__construct($mode);
    }

    public function handle()
    {
        try
        {
            parent::handle();

            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_INIT, [
                'channel'       => $this->params['channel'],
                'accountNumber' => $this->params['accountNumber']
            ]);

            $result = $this->basCore->processStatementForAccount($this->params);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_FAILED,
                ['params' => $this->params]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() <= self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->info(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_RELEASED, [
                'channel'       => $this->params['channel'],
                'accountNumber' => $this->params['accountNumber']
            ]);

            $this->release(self::MAX_RETRY_DELAY);
        }
        else
        {
            $this->trace->error(TraceCode::BANKING_ACCOUNT_STATEMENT_FETCH_JOB_DELETED, [
                'channel'           => $this->params['channel'],
                'accountNumber'     => $this->params['accountNumber'],
                'job_attempts'      => $this->attempts(),
                'message'           => 'Deleting the job after configured number of tries exhaust. Still unsuccessful.'
            ]);

            $this->delete();
        }
    }

    /**
     * Setting queue config key based on bank channel. Specific channel accounts will be pushed to dedicated queues.
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
