<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use Carbon\Carbon;
use RZP\Trace\TraceCode;
use RZP\Constants\Timezone;
use RZP\Models\Transaction;
use RZP\Models\Settlement\Bucket;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Commission\Invoice;
use RZP\Models\Partner\Commission\Constants;

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

    public $timeout = 1800;

    protected $fromTimestamp;

    protected $invoiceId;

    public function __construct(string $mode, string $partnerId, $input)
    {
        parent::__construct($mode);

        $this->partnerId   = $partnerId;

        $this->toTimestamp = $input['to'] ?? null;

        $this->fromTimestamp = $input['from'] ?? null;

        $this->invoiceId = $input[Constants::INVOICE_ID] ?? null;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(
                TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_REQUEST,
                [
                    'mode'          => $this->mode,
                    'partner_id'    => $this->partnerId,
                    'toTimestamp'   => $this->toTimestamp,
                    'fromTimestamp' => $this->fromTimestamp,
                    'invoice_id'    => $this->invoiceId,
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

                foreach ($transactions as $transaction)
                {
                    try
                    {
                        $source = $transaction->source;

                        // skip if some commission on hold clear is already done so that we don't create tds again
                        if ($transaction->isOnHold() === false)
                        {
                            continue;
                        }

                        $txn = $core->setOnHoldFalse($transaction);

                        $totalTax               += $source->getTax();
                        $totalCommissionWithTax += ($source->getCredit() - $source->getDebit());

                        $summary['success_count']++;

                        $successTxnIds[] = $transaction->getId();

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

            list($totalTds) = $core->calculateTds($partner, $totalCommission);

            $summary['total_tax']        = $totalTax;
            $summary['total_commission'] = $totalCommission;
            $summary['total_tds']        = $totalTds;

            $this->trace->info(TraceCode::COMMISSION_TRANSACTION_ON_HOLD_CLEAR_SUMMARY, $summary);

            if ($totalTds > 0)
            {
                $core->createCommissionTds($partner, $totalTds);
            }

            if (empty($this->invoiceId) === false)
            {
                $invoice = $this->repoManager->commission_invoice->findOrFail($this->invoiceId);

                $invoice->setStatus(Invoice\Status::PROCESSED);

                $this->repoManager->saveOrFail($invoice);

                CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId());
            }

            // dispatch for settlement bucketing if at least one commission transaction on hold is cleared
            if (empty($txn) === false)
            {
                $settledAt = Carbon::now(Timezone::IST)->getTimestamp();

                $txn->setSettledAt($settledAt);

                $bucketCore = new Bucket\Core;

                $status = $bucketCore->shouldProcessViaNewService($txn->getMerchantId());

                $bucketCore->dispatchForBucketingOnTransactionHoldToggle($txn, $successTxnIds, $status, null);
            }

            $this->delete();
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
                    'fromTimestamp' => $this->fromTimestamp,
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
