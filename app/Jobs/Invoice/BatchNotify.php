<?php

namespace RZP\Jobs\Invoice;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Jobs\Job as BaseJob;
use RZP\Models\Invoice as InvoiceModel;
use RZP\Jobs\Invoice\Job as InvoiceJob;

/**
 * - Asynchronously sends notification all issued invoices/payment links of given batch.
 */
class BatchNotify extends BaseJob implements ShouldQueue
{
    use InteractsWithQueue;

    const INPUT              = 'input';

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

        $tracePayload = [
            Batch\Entity::ID => $this->batchId,
        ];

        $this->trace->debug(TraceCode::INVOICE_BATCH_NOTIFY_JOB_RECEIVED, $tracePayload + [self::INPUT => $input]);

        try
        {
            $smsNotify   = (bool) ($this->input[InvoiceModel\Entity::SMS_NOTIFY] ?? '1');
            $emailNotify = (bool) ($this->input[InvoiceModel\Entity::EMAIL_NOTIFY] ?? '1');

            $invoices = $this->repoManager->invoice->findIssuedByBatchId($this->batchId);

            foreach ($invoices as $invoice)
            {
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

    protected function notify(
        InvoiceModel\Entity $invoice,
        bool $smsNotify,
        bool $emailNotify)
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

            $job = new InvoiceJob($this->mode, InvoiceJob::ISSUED, $invoice->getId());
            (new DispatchRouter)->dispatchOn($job, DispatchRouter::INVOICE);
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

    protected function init()
    {
        parent::init();

        $this->core = new InvoiceModel\Core;
    }
}
