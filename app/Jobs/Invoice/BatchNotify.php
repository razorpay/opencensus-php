<?php

namespace RZP\Jobs\Invoice;

use RZP\Jobs\Job;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Models\Invoice as InvoiceModel;

/**
 * - Asynchronously sends notification all issued invoices/payment links of given batch.
 */
class BatchNotify extends Job
{
    const INPUT = 'input';

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

    /**
     * Input passed in request. Holds few additional parameters
     *
     * - sms_notify, email_notify: Used to decided whether to send notifications or not
     *
     * @var array
     */
    protected $input;

    public function __construct(string $mode, string $batchId, array $input)
    {
        parent::__construct($mode);

        $this->batchId = $batchId;
        $this->input   = $input;
    }

    public function handle()
    {
        parent::handle();

        $tracePayload = [
            Batch\Entity::ID => $this->batchId,
        ];

        $this->trace->debug(TraceCode::INVOICE_BATCH_NOTIFY_JOB_RECEIVED,
                            $tracePayload + [self::INPUT => $this->input]);

        try
        {
            $smsNotify   = (bool) ($this->input[InvoiceModel\Entity::SMS_NOTIFY] ?? '1');
            $emailNotify = (bool) ($this->input[InvoiceModel\Entity::EMAIL_NOTIFY] ?? '1');

            $invoiceIds = $this->repoManager->invoice->findIssuedByBatchId($this->batchId);

            foreach ($invoiceIds as $invoiceId)
            {
                $invoice = $this->repoManager->invoice->findOrFail($invoiceId);

                $this->notify($invoice, $smsNotify, $emailNotify);
            }

            $this->trace->debug(TraceCode::INVOICE_BATCH_NOTIFY_JOB_HANDLED, $tracePayload);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::INVOICE_BATCH_NOTIFY_JOB_ERROR,
                $tracePayload);
        }
    }

    protected function notify(InvoiceModel\Entity $invoice, bool $smsNotify, bool $emailNotify)
    {
        // Updates invoice's sms and email status to pending
        // so Notifier picks them.
        if ($smsNotify === true)
        {
            $invoice->setSmsStatus(InvoiceModel\NotifyStatus::PENDING);
        }

        if ($emailNotify === true)
        {
            $invoice->setEmailStatus(InvoiceModel\NotifyStatus::PENDING);
        }

        try
        {
            $this->repoManager->saveOrFail($invoice);

            BatchJob::dispatch($this->mode, BatchJob::ISSUED, $invoice->getId());
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::INVOICE_BATCH_NOTIFY_JOB_INV_NOTIFY_ERROR,
                [
                    'batch_id'   => $this->batchId,
                    'invoice_id' => $invoice->getId(),
                ]);
        }
    }
}
