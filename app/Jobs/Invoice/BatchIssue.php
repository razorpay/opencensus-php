<?php

namespace RZP\Jobs\Invoice;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Jobs\Job as BaseJob;
use Razorpay\Trace\Logger as Trace;
use RZP\Models\Invoice as InvoiceModel;

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
            $smsNotify   = (bool) ($this->input[InvoiceModel\Entity::SMS_NOTIFY] ?? '1');
            $emailNotify = (bool) ($this->input[InvoiceModel\Entity::EMAIL_NOTIFY] ?? '1');

            $timeStarted = microtime(true);

            $invoices = $this->repoManager
                             ->invoice
                            ->findByBatchIdAndPublicIds($this->batchId, $ids);

            foreach ($invoices as $invoice)
            {
                $this->issueInvoiceAndNotify($invoice, $smsNotify, $emailNotify);
            }

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
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::INVOICE_BATCH_ISSUE_JOB_ERROR,
                            [
                                Batch\Entity::ID => $this->batchId,
                            ]);
        }
        finally
        {
            $this->delete();
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
        // Setting email_status and sms_status as pending so
        // Notifier picks them

        if ($emailNotify === true)
        {
            $invoice->setEmailStatus(InvoiceModel\NotifyStatus::PENDING);
        }

        if ($smsNotify === true)
        {
            $invoice->setSmsStatus(InvoiceModel\NotifyStatus::PENDING);
        }

        try
        {
            $this->core->issue($invoice, $invoice->merchant);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                            $e,
                            null,
                            TraceCode::INVOICE_BATCH_ISSUE_JOB_ERROR,
                            [
                                'batch_id'   => $this->batchId,
                                'invoice_id' => $invoice->getId(),
                            ]);
        }
    }
}
