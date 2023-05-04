<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Trace\TraceCode;
use RZP\Models\Partner\Commission;

class CommissionFinanceTriggeredOnHoldClear extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    const COMMISSIONS_TRANSACTION_FETCH_LIMIT = 1000;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $partnerId;

    protected $toTimestamp;

    public $timeout = 1800;

    protected $fromTimestamp;

    public function __construct(string $mode, string $partnerId, $input)
    {
        parent::__construct($mode);

        $this->partnerId   = $partnerId;

        $this->toTimestamp = $input['to'] ?? null;

        $this->fromTimestamp = $input['from'] ?? null;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FT_REQUEST,
                [
                    'mode'          => $this->mode,
                    'partner_id'    => $this->partnerId,
                    'toTimestamp'   => $this->toTimestamp,
                    'fromTimestamp' => $this->fromTimestamp,
                ]);

            $core = new Commission\Core;

            $txn                    = null;
            $afterId                = null;
            $totalTax               = 0;
            $totalCommissionWithTax = 0;
            $successTxnIds          = [];

            $partner = $this->repoManager->merchant->findOrFail($this->partnerId);

            $summary = [
                'failed_ids'    => [],
                'failed_count'  => 0,
                'success_count' => 0,
            ];

            while (true)
            {
                // fetch txns in batches and process
                $transactions = $this->repoManager->transaction->fetchUnsettledCommissionTransactions(
                    $partner,
                    $this->fromTimestamp,
                    $this->toTimestamp,
                    self::COMMISSIONS_TRANSACTION_FETCH_LIMIT,
                    $afterId);

                if ($transactions->isEmpty() === true)
                {
                    break;
                }

                $afterId = $transactions->last()->getId();

                CommissionOnHoldClear::dispatch($this->mode, $transactions->getIds());

                foreach ($transactions as $transaction)
                {
                    try
                    {
                        $source = $transaction->source;

                        $totalTax               += $source->getTax();
                        $totalCommissionWithTax += ($source->getCredit() - $source->getDebit());

                        $summary['success_count']++;

                        $successTxnIds[] = $transaction->getId();

                    }
                    catch (\Throwable $e)
                    {
                        $summary['failed_count']++;
                        $summary['failed_ids'][] = $transaction->getId();

                        $this->countJobException($e);

                        $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FT_FAILED
                        );
                    }
                }
            }

            $totalCommission = $totalCommissionWithTax - $totalTax;

            list($totalTds) = $core->calculateTds($partner, $totalCommission);

            $summary['total_tax']        = $totalTax;
            $summary['total_commission'] = $totalCommission;
            $summary['total_tds']        = $totalTds;

            $this->trace->info(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_FT_SUMMARY, $summary);

            if ($totalTds > 0)
            {
                $core->createCommissionTds($partner, $totalTds);
            }

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_TRANSACTION_FT_JOB_ERROR,
                [
                    'mode'        => $this->mode,
                    'partner_id'  => $this->partnerId,
                    'toTimestamp' => $this->toTimestamp,
                    'fromTimestamp' => $this->fromTimestamp,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_QUEUE_FT_DELETE, [
                'mode'         => $this->mode,
                'partner_id'   => $this->partnerId,
                'toTimestamp'  => $this->toTimestamp,
                'fromTimestamp' => $this->fromTimestamp,
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
