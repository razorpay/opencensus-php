<?php

namespace RZP\Jobs\Invoice;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Jobs\Job as BaseJob;
use Razorpay\Trace\Logger as Trace;
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

    /**
     * @var \RZP\Services\Mutex
     */
    protected $mutex;

    public function __construct(string $mode, string $batchId, array $input)
    {
        parent::__construct($mode);

        $this->batchId = $batchId;
        $this->input   = $input;
    }

    public function handle()
    {
        parent::handle();

        $this->trace->debug(
            TraceCode::INVOICE_BATCH_NOTIFY_JOB_RECEIVED,
            [
                Batch\Entity::ID => $this->batchId,
                self::INPUT      => $this->input,
            ]);

        $this->core = new InvoiceModel\Core;

        try{

            $smsNotify   = (bool) ($this->input[InvoiceModel\Entity::SMS_NOTIFY] ?? '1');
            $emailNotify = (bool) ($this->input[InvoiceModel\Entity::EMAIL_NOTIFY] ?? '1');

            $timeStarted = microtime(true);

            $invoices = $this->repoManager
                ->invoice
                ->findIssuedByBatchId($this->batchId);

            foreach ($invoices as $invoice)
            {
                $this->notify($invoice, $smsNotify, $emailNotify);
            }

            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(
                TraceCode::INVOICE_BATCH_NOTIFY_JOB_HANDLED,
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
                TraceCode::INVOICE_BATCH_NOTIFY_JOB_ERROR,
                [
                    Batch\Entity::ID => $this->batchId,
                ]);
        }
        finally
        {
            $this->delete();
        }
    }

    protected function notify(InvoiceModel\Entity $invoice,
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
            $this->core->saveAndNotify($invoice, InvoiceJob::ISSUED);
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                null,
                TraceCode::INVOICE_BATCH_NOTIFY_JOB_ERROR,
                [
                    'batch_id'   => $this->batchId,
                    'invoice_id' => $invoice->getId(),
                ]);
        }
    }
}