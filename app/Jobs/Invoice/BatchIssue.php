<?php

namespace RZP\Jobs\Invoice;

use App;

use RZP\Jobs\Job;
use RZP\Models\Batch;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Invoice as InvoiceModel;

/**
 * - Asynchronously issues all invoices/payment links of given batch.
 */
class BatchIssue extends Job
{
    const INPUT              = 'input';
    const MUTEX_LOCK_TIMEOUT = 3600;    // In seconds

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

        $this->mutex = App::getFacadeRoot()['api.mutex'];

        $this->mutex->acquireAndRelease(
            $this->batchId,
            function ()
            {
                $this->handleBatchIssue();
            },
            static::MUTEX_LOCK_TIMEOUT,
            ErrorCode::BAD_REQUEST_BATCH_ANOTHER_OPERATION_IN_PROGRESS);
    }

    /**
     * Actually handles the job. This method is a callback argument to acquire
     * and release lock(on batch id) block in above handle() method. This way
     * we ensure that only on process(worker) is consuming the batch issue job.
     * Also once this job is processed next one will not have any draft invoices
     * left and so nothing will happen in subsequent duplicate jobs if any via
     * some edge cases.
     *
     * @return void
     */
    protected function handleBatchIssue()
    {
        $this->trace->debug(
            TraceCode::INVOICE_BATCH_ISSUE_JOB_RECEIVED,
            [
                Batch\Entity::ID => $this->batchId,
                self::INPUT      => $this->input,
            ]);

        $this->core = new InvoiceModel\Core;

        try
        {
            $ids         = $this->input[InvoiceModel\Entity::IDS] ?? [];
            $smsNotify   = (bool) ($this->input[InvoiceModel\Entity::SMS_NOTIFY] ?? '1');
            $emailNotify = (bool) ($this->input[InvoiceModel\Entity::EMAIL_NOTIFY] ?? '1');

            $timeStarted = microtime(true);

            $invoices = $this->repoManager
                             ->invoice
                             ->findDraftsByBatchIdAndPublicIds($this->batchId, $ids);

            foreach ($invoices as $invoice)
            {
                $this->issueInvoiceAndNotify($invoice, $smsNotify, $emailNotify, $this->batchId);
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
     * @param string              $batchId
     */
    protected function issueInvoiceAndNotify(
        InvoiceModel\Entity $invoice,
        bool $smsNotify,
        bool $emailNotify,
        string $batchId)
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
            $this->core->issue($invoice, $invoice->merchant, $batchId);
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
