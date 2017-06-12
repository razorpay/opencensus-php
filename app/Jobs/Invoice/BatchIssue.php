<?php

namespace RZP\Jobs\Invoice;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Jobs\Job as BaseJob;
use RZP\Models\Invoice as InvoiceModel;
use RZP\Models\Batch;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

/**
 * Asynchronously issues all invoices/payment links of given batch.
 * Also takes input list of ids and if passed only issues these invoices
 * of the batch.
 *
 */
class BatchIssue extends BaseJob implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Batch entity id.
     *
     * @var string
     */
    protected $batchId;

    /**
     * Optional: List of invoice ids passed
     *
     * @var null|array
     */
    protected $ids;

    /**
     * Invoice's Core instance
     *
     * @var InvoiceModel\Core
     */
    protected $core;

    public function __construct(string $mode, string $batchId, array $ids)
    {
        parent::__construct($mode);

        $this->batchId = $batchId;
        $this->ids     = $ids;
    }

    public function handle()
    {
        parent::handle();

        $this->core = new InvoiceModel\Core;

        try
        {

            $this->trace->debug(
                            TraceCode::INVOICE_BATCH_ISSUE_JOB_RECEIVED,
                            [
                                Batch\Entity::ID         => $this->batchId,
                                InvoiceModel\Entity::IDS => $this->ids,
                            ]);

            $timeStarted = microtime(true);

            $invoices = $this->repoManager->invoice
                                          ->findByBatchIdAndIds(
                                                $this->batchId,
                                                $this->ids);

            foreach ($invoices as $invoice)
            {
                $this->issueInvoiceAndNotify($invoice);
            }

            $this->delete();

            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(
                            TraceCode::INVOICE_BATCH_ISSUE_JOB_HANDLED,
                            [
                                Batch\Entity::ID => $this->batchId,
                                'time_taken'     => $timeTaken,
                            ]);
        }
        catch (\Throwable $e)
        {
            $this->delete();

            $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::INVOICE_BATCH_ISSUE_JOB_ERROR,
                            [
                                Batch\Entity::ID => $this->batchId,
                            ]);
        }
    }

    /**
     * Issues invoice and notifies customer.
     *
     * @param InvoiceModel\Entity $invoice
     */
    protected function issueInvoiceAndNotify(InvoiceModel\Entity $invoice)
    {
        try
        {
            $this->repoManager->invoice->transaction(function () use ($invoice)
            {
                (new InvoiceModel\Generator($invoice->merchant, $invoice))->issueInvoice();

                $this->repoManager->invoice->saveOrFail($invoice);
            });

            $pdfPath = $this->core->createInvoicePdf($invoice);

            (new InvoiceModel\Notifier($invoice, $pdfPath))->notifyInvoiceIssuedToCustomer();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            Trace::ERROR,
                            TraceCode::INVOICE_BATCH_ISSUE_JOB_ERROR,
                            [
                                'batch_id'   => $this->batchId,
                                'invoice_id' => $invoice->getId(),
                            ]);
        }
    }
}
