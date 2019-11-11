<?php

namespace RZP\Jobs\Settlement;

use App;
use Razorpay\Trace\Logger;

use RZP\Error\ErrorCode;
use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Attempt;

class Initiate extends Job
{
    const RELEASE_TIME       = 30;

    const MAX_ATTEMPTS       = 10;

    const MUTEX_LOCK_TIMEOUT = 30;

    const MUTEX_RESOURCE     = 'SETTLEMENT_INITIATE_%s';

    /**
     * @var string
     */
    protected $queueConfigKey = 'settlement_initiate';

    /**
     * @var string
     */
    protected $channel;

    /**
     * @param string $mode
     * @param string $channel
     */
    public function __construct(string $mode, string $channel)
    {
        parent::__construct($mode);

        $this->channel      = $channel;
    }

    /**
     * Process queue request
     */
    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::SETTLEMENT_INITIATE_JOB,
                [
                    'channel'       => $this->channel,
                ]);

            $mutex = App::getFacadeRoot()['api.mutex'];

            $resource = sprintf(self::MUTEX_RESOURCE, $this->channel);

            $mutex->acquireAndRelease(
                $resource,
                function ()
                {
                    $input = [
                        'purpose'     => Attempt\Purpose::SETTLEMENT,
                        'source_type' => Attempt\Type::SETTLEMENT,
                    ];

                    (new Attempt\Service)->initiateFundTransfers($input, $this->channel);
                },
                static::MUTEX_LOCK_TIMEOUT,
                ErrorCode::BAD_REQUEST_ANOTHER_OPERATION_IN_PROGRESS);

        }
        catch (\Throwable $e)
        {
            // if the max attempt is not exhausted then release the job for retry
            if ($this->attempts() <= self::MAX_ATTEMPTS)
            {
                $this->release(self::RELEASE_TIME);
            }

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::FUND_TRANSFER_INITIATION_FAILED,
                [
                    'channel' => $this->channel,
                ]);
        }
    }
}
