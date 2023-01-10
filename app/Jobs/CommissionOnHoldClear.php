<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Services\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Metric as PartnerMetric;

class CommissionOnHoldClear extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

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

            $timeStarted = microtime(true);

            $txn           = null;
            $successTxnIds = [];

            $summary = [
                'failed_ids'    => [],
                'failed_count'  => 0,
                'success_count' => 0,
            ];

            $this->trace->info(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_REQUEST, ['transactions' => $this->transactions]);

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

                    $this->countJobException($e);

                    $this->trace->traceException(
                        $e,
                        Trace::ERROR,
                        TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FAILED
                    );
                    $this->trace->count(PartnerMetric::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FAILED_TOTAL);
                }
            }

            $this->trace->info(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_SUMMARY, $summary);

            if (empty($txn) === false)
            {
                (new Commission\CommissionOnHoldUtility())->dispatchForSettlement($txn, $successTxnIds);
            }

            $timeTaken = microtime(true) - $timeStarted;

            $timeTakenMilliSeconds = (int) $timeTaken * 1000;

            $this->trace->histogram(PartnerMetric::COMMISSION_ON_HOLD_CLEAR_PROCESS_TIME_MS, $timeTakenMilliSeconds);

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
            $this->trace->count(PartnerMetric::COMMISSION_TRANSACTION_JOB_EXHAUSTED_TOTAL);

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

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
