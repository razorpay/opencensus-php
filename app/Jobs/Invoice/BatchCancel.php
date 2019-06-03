<?php

namespace RZP\Jobs\Invoice;

use RZP\Jobs\Job;
use RZP\Trace\TraceCode;
use RZP\Models\Invoice as InvoiceModel;

/**
 * - Asynchronously cancels all issued invoices/payment links of given batch.
 */
class BatchCancel extends Job
{
    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'batch';

    /**
     * Batch entity id.
     *
     * @var string
     */
    protected $batchId;

    public function __construct(string $mode, string $batchId)
    {
        parent::__construct($mode);

        $this->batchId = $batchId;
    }

    public function handle()
    {
        parent::handle();

        $invoices = $this->repoManager->invoice->findIssuedByBatchId($this->batchId);

        $summary = [
            'total_invoices_count' => $invoices->count(),
            'failed_invoice_ids'   => [],
        ];

        foreach ($invoices as $invoice)
        {
            $this->cancel($invoice, $summary);
        }

        $this->trace->debug(TraceCode::INVOICE_BATCH_CANCEL_SUMMARY, $summary);
    }

    protected function cancel(InvoiceModel\Entity $invoice, array & $summary)
    {
        try
        {
            (new InvoiceModel\Core())->cancelInvoice($invoice);
        }
        catch (\Throwable $e)
        {
            $summary['failed_invoice_ids'][] = $invoice->getId();

            $this->trace->traceException(
                $e,
                null,
                TraceCode::INVOICE_BATCH_CANCEL_JOB_INV_CANCEL_ERROR,
                [
                    'batch_id'   => $this->batchId,
                    'invoice_id' => $invoice->getId(),
                ]);
        }
    }
}
