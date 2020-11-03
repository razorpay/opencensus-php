<?php

namespace RZP\Jobs\Invoice;

use RZP\Jobs\Job;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Invoice as InvoiceModel;
use RZP\Models\Merchant\Entity as MerchantEntity;

/**
 * - Asynchronously cancels all issued invoices/payment links of given batch.
 */
class BatchCancel extends Job
{
    const RETRY_DELAY           = 10;

    const MAX_RETRY_ATTEMPTS    = 5;
    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'merchant_invoice';

    /**
     * Batch entity id.
     *
     * @var string
     */
    protected $batchId;

    protected $successCount;

    /**
     * Invoice's Core instance
     *
     * @var InvoiceModel\Core
     */
    protected $core;

    protected $merchant;

    public $timeout = 900;

    const TOTAL_INVOICES_COUNT = 'total_invoices_count';
    const FAILED_INVOICE_IDS   = 'failed_invoice_ids';

    public function __construct(string $mode, string $batchId, int $successCount)
    {
        parent::__construct($mode);

        $this->batchId = $batchId;

        $this->successCount = $successCount;
    }

    public function handle()
    {
        parent::handle();

        $this->core = new InvoiceModel\Core;

        $summary = [
            self::TOTAL_INVOICES_COUNT => 0,
            self::FAILED_INVOICE_IDS   => [],
        ];

        //
        // fetch invoices created by batch in chunks, and cancel each one
        // do this until there is no more invoices left to cancel
        // and fetched invoice count is less than total count of batch
        //
        $invoices = $this->repoManager->invoice->findIssuedByBatchIdWithLimit($this->batchId);

        while (count($invoices) > 0)
        {
            $summary[self::TOTAL_INVOICES_COUNT] += count($invoices);

            foreach ($invoices as $invoice)
            {
                $this->cancel($invoice, $summary);
            }

            $invoices = $this->repoManager->invoice->findIssuedByBatchIdWithLimit($this->batchId);
        }

        $this->trace->info(TraceCode::INVOICE_BATCH_CANCEL_SUMMARY, $summary);
    }

    protected function cancel(InvoiceModel\Entity $invoice, array & $summary)
    {
        try
        {
            $this->core->cancelInvoice($invoice);
        }
        catch (\Throwable $e)
        {
            $summary[self::FAILED_INVOICE_IDS][] = $invoice->getId();

            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::INVOICE_BATCH_CANCEL_JOB_INV_CANCEL_ERROR,
                [
                    'batch_id'   => $this->batchId,
                    'invoice_id' => $invoice->getId(),
                ]);
        }
    }
}
