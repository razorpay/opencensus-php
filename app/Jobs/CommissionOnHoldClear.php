<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use Carbon\Carbon;
use RZP\Services\Metric;
use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Metric as PartnerMetric;
use RZP\Constants\Timezone;
use RZP\Models\Settlement\Bucket;

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

    protected $startTimestamp;

    public function __construct(string $mode, array $transactions, int $startTimestamp)
    {
        parent::__construct($mode);

        $this->transactions = $transactions;
        $this->startTimestamp = $startTimestamp;
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

            // NOTE:
            // subtracting 1 month here because
            // in current month we generate previous month's invoice and
            // the $startTimestamp would be previous month's start timestamp
            // hence, for old invoices we want to check if the startTimestamp
            // is older than previous month's startTimestamp
            $currentMonth = Carbon::now()->subMonth()->month;
            $currentYear = Carbon::now()->year;
            $currentTimestamp = Carbon::createFromDate($currentYear, $currentMonth, 1, Timezone::IST)->startOfMonth()->getTimestamp();

            if ($this->startTimestamp < $currentTimestamp) {
                $this->handleOldInvoice($this->transactions, $timeStarted);
                return;
            }

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

    protected function handleOldInvoice(array $transactions, int $timeStarted): void
    {
        $summary = [
            'success_count' => 0,
            'success_ids' => [],
        ];
        $bucketCore = new Bucket\Core;
        $res = $bucketCore->settlementServiceToggleTransactionHold($transactions, null);
        $timeTakenMilliSeconds = (int) (microtime(true) - $timeStarted) * 1000;
        if ($res['success'] === true) {
            $summary['success_count'] = count($transactions);
            $summary['success_ids'] = $transactions;
            $this->trace->info(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_SUMMARY, $summary);

            // Track successful clearances
            $this->trace->histogram(PartnerMetric::COMMISSION_ON_HOLD_CLEAR_OLD_INVOICE_PROCESS_TIME_MS, $timeTakenMilliSeconds);
            return;
        }

        $this->trace->error(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FAILED, $res);

        // Track failed clearances
        $this->trace->count(PartnerMetric::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_OLD_INVOICE_FAILED_TOTAL);

        // Track process time for failures too
        $this->trace->histogram(PartnerMetric::COMMISSION_ON_HOLD_CLEAR_OLD_INVOICE_PROCESS_TIME_MS, $timeTakenMilliSeconds);
    }
}
