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
    const RETRY_DELAY           = 60;

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

    const TOTAL_INVOICES_COUNT = 'total_invoices_count';
    const FAILED_INVOICE_IDS   = 'failed_invoice_ids';

    public function __construct(string $mode, string $batchId, int $successCount, MerchantEntity $merchant = null)
    {
        parent::__construct($mode);

        $this->batchId = $batchId;

        $this->successCount = $successCount;

        $this->merchant = $merchant;
    }

    public function handle()
    {
        parent::handle();

        $batch = [];

        if (empty($this->merchant) === false)
        {
            $batch = (new Batch\Service())->getBatchById($this->batchId, $this->merchant);
        }
        else
        {
            // fetchBatchById() checks for admin auth. Is this check possible from here?
            $batch = (new Batch\Service())->fetchBatchById($this->batchId);
        }

        if ($batch === [])
        {
            $this->trace->debug(
                TraceCode::BATCH_NOT_FOUND, // To be changed
                [
                    'batch_id'      => $this->batchId,
                    'merchant_id'   => $this->merchant ? $this->merchant->getId() : null,
                ]
            );

            $this->delete();

            return;
        }

        $batchStatus = $batch[Batch\Entity::STATUS];

        if (($batchStatus !== Batch\Status::PROCESSED) and
            ($batchStatus !== Batch\Status::CANCELLED))
        {
            if ($this->attempts() <= self::MAX_RETRY_ATTEMPTS)
            {
                $this->release(self::RETRY_DELAY);
            }
            else
            {
                $this->delete();
            }

            return;
        }

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
        while ($this->successCount > $summary[self::TOTAL_INVOICES_COUNT])
        {
            $invoices = $this->repoManager->invoice->findIssuedByBatchIdWithLimit($this->batchId);

            if (count($invoices) === 0)
            {
                $this->trace->debug(TraceCode::INVOICE_BATCH_COUNT_ZERO, [$this->batchId]);

                break;
            }

            $summary[self::TOTAL_INVOICES_COUNT] += count($invoices);

            foreach ($invoices as $invoice)
            {
                $this->cancel($invoice, $summary);
            }
        }

        $this->trace->debug(TraceCode::INVOICE_BATCH_CANCEL_SUMMARY, $summary);

        $this->delete();
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
