<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use App;
use RZP\Error\ErrorCode;
use RZP\Models\Partner\Metric;
use RZP\Services\Mutex;
use RZP\Trace\TraceCode;
use RZP\Services\Workflow;
use RZP\Base\RuntimeManager;
use RZP\Models\Partner\Commission;
use RZP\Models\Partner\Commission\Invoice;
use RZP\Models\Partner\Metric as PartnerMetric;

class CommissionTdsSettlement extends Job
{
    const MUTEX_LOCK_TIMEOUT = 3000;

    const COMMISSIONS_TRANSACTION_FETCH_LIMIT = 2500;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    /**
     * @var Mutex
     */
    protected $mutex;

    protected $partnerId;

    protected $updateInvoiceStatus = true;

    protected $skipProcessed = false;

    protected $createTds = true;

    protected $toTimestamp;

    public    $timeout        = 1800;

    protected $fromTimestamp;

    protected $invoiceId;

    protected $isPartnerAutoApproveEnabled = false;

    public function __construct(string $mode, string $partnerId, $input)
    {
        parent::__construct($mode);

        $this->partnerId = $partnerId;

        $this->toTimestamp = $input['to'] ?? null;

        $this->fromTimestamp = $input['from'] ?? null;

        $this->invoiceId = $input[Invoice\Constants::INVOICE_ID];

        $this->updateInvoiceStatus = $input[Invoice\Constants::UPDATE_INVOICE_STATUS] ?? true;
        $this->createTds = $input[Invoice\Constants::CREATE_TDS] ?? true;
        $this->skipProcessed = $input[Invoice\Constants::SKIP_PROCESSED] ?? false;
        $this->isPartnerAutoApproveEnabled = $input[Commission\Constants::INVOICE_AUTO_APPROVED] ?? false;
    }

    public function handle()
    {
        parent::handle();

        $this->resetWorkflowSingleton();

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
            $this->traceOptions();

            if (($this->skipProcessed === false) and ($invoice->getStatus() === Invoice\Status::PROCESSED))
            {
                $this->delete();

                return;
            }

            $timeStarted = microtime(true);

            $core = new Commission\Core;

            $afterId = null;

            $partner = $this->repoManager->merchant->findOrFail($this->partnerId);

            $batchCount = 1;

            while (true)
            {
                $this->trace->info(TraceCode::COMMISSION_TRANSACTION_FETCH_START, ['batch_count' => $batchCount]);

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

                $batchCount++;
            }

            if ($this->createTds === true)
            {
                $totalCommission = $invoice->getGrossAmount() - $invoice->getTaxAmount();
                $totalTax        = $invoice->getTaxAmount();

                list($totalTds, $tdsPercentage) = $core->calculateTds($partner, $totalCommission);

                $summary['total_tax']        = $totalTax;
                $summary['total_commission'] = $totalCommission;
                $summary['total_tds']        = $totalTds;
                $summary['tds_percentage']   = $tdsPercentage;

                $this->trace->info(TraceCode::COMMISSION_TDS_SETTLEMENT_SUMMARY, $summary);

                if ($totalTds > 0)
                {
                    $core->createCommissionTds($partner, $totalTds);
                }
            }

            if ($this->updateInvoiceStatus === true)
            {
                $invoice->setStatus(Invoice\Status::PROCESSED);

                $this->repoManager->saveOrFail($invoice);

                CommissionInvoiceAction::dispatch($this->mode, $invoice->getStatus(), $invoice->getId(), [Commission\Constants::INVOICE_AUTO_APPROVED => $this->isPartnerAutoApproveEnabled]);
            }

            $timeTaken = microtime(true) - $timeStarted;

            $timeTakenMilliSeconds = (int) $timeTaken * 1000;

            $this->trace->histogram(PartnerMetric::COMMISSION_TDS_SETTLEMENT_PROCESS_TIME_MS, $timeTakenMilliSeconds);
            $this->trace->count(Metric::COMMISSION_TDS_SETTLEMENT_TOTAL);
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
                    'invoice_id'    => $this->invoiceId
                ]
            );

            $this->countJobException($e);

            // TODO can be removed after grafan boards are updated to use new metric
            $this->trace->count(PartnerMetric::COMMISSION_TDS_SETTLEMENT_JOB_FAILURE_TOTAL);
            $this->delete();
        }
    }

    protected function traceOptions()
    {
        $this->trace->info(
            TraceCode::COMMISSION_TDS_SETTLEMENT_REQUEST,
            [
                'mode'           => $this->mode,
                'partner_id'     => $this->partnerId,
                'toTimestamp'    => $this->toTimestamp,
                'fromTimestamp'  => $this->fromTimestamp,
                'invoice_id'     => $this->invoiceId,
                'skip_processed' => $this->skipProcessed,
                'create_tds'     => $this->createTds,
                'update_status'  => $this->updateInvoiceStatus,
            ]);
    }

    private function resetWorkflowSingleton()
    {
        $app = App::getFacadeRoot();
        $app['workflow'] =  new Workflow\Service($app);
    }
}
