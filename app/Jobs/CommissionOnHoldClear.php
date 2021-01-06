<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission;

class CommissionOnHoldClear extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';


    public $timeout = 1800;

    protected $transactions;

    public function __construct(string $mode, array $transactions)
    {
        parent::__construct($mode);

        $this->transactions = $transactions;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $core = new Commission\Core;

            $txn           = null;
            $successTxnIds = [];

            $summary = [
                'failed_ids'    => [],
                'failed_count'  => 0,
                'success_count' => 0,
            ];

            foreach ($this->transactions as $transactionId)
            {
                try
                {
                    $txn = $core->setOnHoldFalse($transactionId);

                    $summary['success_count']++;

                    $successTxnIds[] = $transactionId;

                }
                catch (\Throwable $e)
                {
                    $summary['failed_count']++;
                    $summary['failed_ids'][] = $transactionId;

                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FAILED
                    );
                }
            }

            (new Commission\CommissionOnHoldUtility())->dispatchForSettlement($txn, $successTxnIds);

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_TRANSACTION_JOB_ERROR,
                [
                    'mode'       => $this->mode,
                ]
            );

            $this->checkRetry();
        }
    }

    protected function checkRetry()
    {
        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_QUEUE_DELETE, [
                'mode'         => $this->mode,
                'job_attempts' => $this->attempts(),
                'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
