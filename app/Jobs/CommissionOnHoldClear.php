<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use Carbon\Carbon;

use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Partner\Commission;

class CommissionOnHoldClear extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    const COMMISSIONS_TRANSACTION_FETCH_LIMIT = 1000;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $partnerId;

    protected $toTimestamp;

    public function __construct(string $mode, string $partnerId, int $toTimestamp)
    {
        parent::__construct($mode);

        $this->partnerId   = $partnerId;
        $this->toTimestamp = $toTimestamp;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_REQUEST,
                [
                    'mode'        => $this->mode,
                    'partner_id'  => $this->partnerId,
                    'toTimestamp' => $this->toTimestamp,
                ]);

            $core = new Commission\Core;

            $txn                    = null;
            $afterId                = null;
            $totalTax               = 0;
            $totalCommissionWithTax = 0;

            $partner = $this->repoManager->merchant->findOrFail($this->partnerId);

            $summary = [
                'failed_ids'    => [],
                'failed_count'  => 0,
                'success_count' => 0,
            ];

            while (true)
            {
                // fetch txns in batches and process
                $transactions = $this->repoManager->transaction->fetchPartnerCommissionTransactionsOnHold(
                    $this->partnerId,
                    $this->toTimestamp,
                    self::COMMISSIONS_TRANSACTION_FETCH_LIMIT,
                    $afterId);

                if ($transactions->isEmpty() === true)
                {
                    break;
                }

                $afterId = $transactions->last()->getId();

                foreach ($transactions as $transaction)
                {
                    try
                    {
                        $totalTax               += $transaction->source->getTax();
                        $totalCommissionWithTax += $transaction->source->getCredit();

                        $txn = $core->setOnHoldFalse($transaction);

                        $summary['success_count']++;
                    }
                    catch (\Throwable $e)
                    {
                        $summary['failed_count']++;
                        $summary['failed_ids'][] = $transaction->getId();

                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FAILED
                        );
                    }
                }
            }

            $totalCommission = $totalCommissionWithTax - $totalTax;

            $totalTds = $core->calculateTds($partner, $totalCommission);

            $summary['total_tax']        = $totalTax;
            $summary['total_commission'] = $totalCommission;
            $summary['total_tds']        = $totalTds;

            $this->trace->info(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_SUMMARY, $summary);

            if ($totalTds > 0)
            {
                $core->createAdjustmentForTds($partner, $totalTds);
            }

            // dispatch for settlement bucketing if at least one transaction is processed
            if (empty($txn) === false)
            {
                $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

                (new Transaction\Core)->dispatchForSettlementBucketing($txn, $settledAt);
            }
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_TRANSACTION_JOB_ERROR,
                [
                    'mode'        => $this->mode,
                    'partner_id'  => $this->partnerId,
                    'toTimestamp' => $this->toTimestamp,
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
                'partner_id'   => $this->partnerId,
                'toTimestamp'  => $this->toTimestamp,
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
