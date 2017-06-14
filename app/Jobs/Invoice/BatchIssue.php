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
 * - Asynchronously issues all invoices/payment links of given batch.
 */
class BatchIssue extends BaseJob implements ShouldQueue
{
    use InteractsWithQueue;

    const INPUT = 'input';

    /**
     * Batch entity id.
     *
     * @var string
     */
    protected $batchId;

    /**
     * Input passed in request. Holds few additional parameters
     *
     * - ids: Only these invoices of batch are issued
     * - sms_notify, email_notify: Used to decided whether to send notifications or not
     *
     * @var array
     */
    protected $input;

    /**
     * Invoice's Core instance
     *
     * @var InvoiceModel\Core
     */
    protected $core;

    public function __construct(string $mode, string $batchId, array $input)
    {
        parent::__construct($mode);

        $this->batchId = $batchId;
        $this->input   = $input;
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
                                Batch\Entity::ID => $this->batchId,
                                self::INPUT      => $this->input,
                            ]);

            $ids         = $this->input[InvoiceModel\Entity::IDS] ?? [];
            $smsNotify   = boolval($this->input[InvoiceModel\Entity::SMS_NOTIFY] ?? '1');
            $emailNotify = boolval($this->input[InvoiceModel\Entity::EMAIL_NOTIFY] ?? '1');

            $timeStarted = microtime(true);

            $invoices = $this->repoManager
                             ->invoice
                            ->findByBatchIdAndIds($this->batchId, $ids);

            foreach ($invoices as $invoice)
            {
                $this->issueInvoiceAndNotify($invoice, $smsNotify, $emailNotify);
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
     * @param boolean             $smsNotify
     * @param boolean             $emailNotify
     */
    protected function issueInvoiceAndNotify(
        InvoiceModel\Entity $invoice,
        bool $smsNotify,
        bool $emailNotify)
    {
        try
        {
            $this->repoManager->invoice->transaction(function () use ($invoice)
            {
                (new InvoiceModel\Generator($invoice->merchant, $invoice))->issueInvoice();

                $this->repoManager->invoice->saveOrFail($invoice);
            });

            $pdfPath = $this->core->createInvoicePdf($invoice);

            $notifier = new InvoiceModel\Notifier($invoice, $pdfPath);

            if ($smsNotify === true)
            {
                $notifier->smsInvoiceIssuedToCustomer();
            }

            if ($emailNotify === true)
            {
                $notifier->emailInvoiceIssuedToCustomer();
            }

            if (($smsNotify === true) or ($emailNotify === true))
            {
                $this->repoManager->invoice->saveOrFail($invoice);
            }
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
