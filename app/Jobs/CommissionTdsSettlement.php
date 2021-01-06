<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use App;
use RZP\Error\ErrorCode;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Base\RuntimeManager;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Commission\Invoice;
use RZP\Models\Partner\Commission\Constants;

class CommissionTdsSettlement extends Job
{
    const RETRY_INTERVAL = 300;

    const MAX_RETRY_ATTEMPT = 5;

    const MUTEX_LOCK_TIMEOUT = 3000;

    const COMMISSIONS_TRANSACTION_FETCH_LIMIT = 5000;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    /**
     * @var Mutex
     */
    protected $mutex;

    protected $partnerId;

    protected $toTimestamp;

    public    $timeout        = 1800;

    protected $fromTimestamp;

    protected $invoiceId;

    public function __construct(string $mode, string $partnerId, $input)
    {
        parent::__construct($mode);

        $this->partnerId = $partnerId;

        $this->toTimestamp = $input['to'] ?? null;

        $this->fromTimestamp = $input['from'] ?? null;

        $this->invoiceId = $input[Constants::INVOICE_ID];
    }

    public function handle()
    {
        parent::handle();

        $this->mutex = App::getFacadeRoot()['api.mutex'];

        RuntimeManager::setMemoryLimit('2048M');

        $this->mutex->acquireAndRelease(
            $this->invoiceId,
            function ()
            {
                $invoice = $this->repoManager->commission_invoice->findOrFail($this->invoiceId);

                $this->handleUnSettledCommission($invoice);
            },
            static::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_COMMISSION_TDS_SETTLEMENT_OPERATION_IN_PROGRESS);
    }

    public function handleUnSettledCommission($invoice)
    {
        try
        {
            $this->trace->info(
                TraceCode::COMMISSION_TDS_SETTLEMENT_REQUEST,
                [
                    'mode'          => $this->mode,
                    'partner_id'    => $this->partnerId,
                    'toTimestamp'   => $this->toTimestamp,
                    'fromTimestamp' => $this->fromTimestamp,
                    'invoice_id'    => $this->invoiceId,
                ]);

            if ($invoice->getStatus() === Invoice\Status::PROCESSED)
            {
                return;
            }

            $core = new Commission\Core;

            $afterId = null;

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
            }

            $totalCommission = $invoice->getGrossAmount() - $invoice->getTaxAmount();
            $totalTax        = $invoice->getTaxAmount();

            list($totalTds) = $core->calculateTds($partner, $totalCommission);

            $summary['total_tax']        = $totalTax;
            $summary['total_commission'] = $totalCommission;
            $summary['total_tds']        = $totalTds;

            $this->trace->info(TraceCode::COMMISSION_TDS_SETTLEMENT_SUMMARY, $summary);

            if ($totalTds > 0)
            {
                $core->createCommissionTds($partner, $totalTds);
            }

            $invoice->setStatus(Invoice\Status::PROCESSED);

            $this->repoManager->saveOrFail($invoice);

            CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId());

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_TDS_SETTLEMENT_JOB_ERROR,
                [
                    'mode'          => $this->mode,
                    'partner_id'    => $this->partnerId,
                    'toTimestamp'   => $this->toTimestamp,
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
            $this->trace->error(TraceCode::COMMISSION_TDS_SETTLEMENT_QUEUE_DELETE, [
                'mode'          => $this->mode,
                'partner_id'    => $this->partnerId,
                'toTimestamp'   => $this->toTimestamp,
                'fromTimestamp' => $this->fromTimestamp,
                'job_attempts'  => $this->attempts(),
                'message'       => 'Deleting the job after configured number of tries. Still unsuccessful.'
            ]);

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
