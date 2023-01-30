<?php

namespace RZP\Jobs;

use Razorpay\Trace\Logger as Trace;

use RZP\Exception;
use RZP\Diag\EventCode;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Models\Partner\Commission\Invoice;
use RZP\Models\Partner\Commission\Constants as CommissionConstants;

class CommissionInvoiceAction extends Job
{
    const RETRY_INTERVAL    = 300;

    const MAX_RETRY_ATTEMPT = 5;

    /**
     * @var string
     */
    protected $queueConfigKey = 'commission';

    protected $metricsEnabled = true;

    protected $id;
    protected $event;
    protected $invoiceData;
    protected $invoice;

    public function __construct(string $mode, string $event, string $id, array $invoiceData = [])
    {
        parent::__construct($mode);

        $this->event       = $event;
        $this->id          = $id;
        $this->invoiceData = $invoiceData;
    }

    public function handle()
    {
        parent::handle();

        try
        {
            $this->trace->info(TraceCode::COMMISSION_INVOICE_ACTION_REQUEST,
                [
                    'mode'   => $this->mode,
                    'event'  => $this->event,
                    'id'     => $this->id,
                    'data'   => $this->invoiceData,
                ]);

            $handler = $this->getHandlerForJobEvent();

            $this->invoice = $this->repoManager->commission_invoice->findOrFailPublic($this->id);

            $this->{$handler}();

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->trace->traceException(
                $e,
                Trace::ERROR,
                TraceCode::COMMISSION_INVOICE_ACTION_ERROR,
                [
                    'mode'   => $this->mode,
                    'data'   => $this->invoiceData,
                ]
            );

            $this->checkRetry($e);
        }
    }

    protected function handleIssued()
    {
        $core    = new Invoice\Core;

        $pdfPath = $core->createInvoicePdfAndGetFilePath($this->invoice);

        $core->sendCommissionInvoiceEvents($this->invoice, EventCode::PARTNERSHIPS_COMMISSION_INVOICE_GENERATED);

        $core->sendCommissionIssuedMail($this->invoice, $pdfPath);

        $core->sendCommissionSms($this->invoice, $pdfPath);
    }

    protected function handleUnderReview()
    {
        $core    = new Invoice\Core;

        $pdfPath = $core->createInvoicePdfAndGetFilePath($this->invoice);

        $core->sendCommissionMail($this->invoice, $pdfPath);

        $core->sendCommissionInvoiceEvents($this->invoice, EventCode::PARTNERSHIPS_COMMISSION_INVOICE_APPROVED);
    }

    protected function handleProcessed()
    {
        $core    = new Invoice\Core;
        $pdfPath = $core->createInvoicePdfAndGetFilePath($this->invoice);

        if (isset($this->invoiceData[CommissionConstants::INVOICE_AUTO_APPROVED]) &&
            $this->invoiceData[CommissionConstants::INVOICE_AUTO_APPROVED] === true)
        {
            $core->sendInvoiceAutoApprovedMail($this->invoice, $pdfPath);
            $core->sendCommissionAutoApprovedSMS($this->invoice, $pdfPath);
        }
        else
        {
            $core->sendCommissionProcessedMail($this->invoice, $pdfPath);
        }

        $core->sendCommissionInvoiceEvents($this->invoice, EventCode::PARTNERSHIPS_COMMISSION_INVOICE_PROCESSED);
    }

    protected function getHandlerForJobEvent(): string
    {
        $handler = 'handle' . studly_case($this->event);

        if (method_exists($this, $handler) === false)
        {
            $error = "InvoiceAction: Handler - $handler not found.";

            throw new Exception\LogicException($error, ErrorCode::SERVER_ERROR_MISSING_HANDLER);
        }

        return $handler;
    }

    protected function checkRetry(\Throwable $e)
    {
        $this->countJobException($e);

        if ($this->attempts() > self::MAX_RETRY_ATTEMPT)
        {
            $this->trace->error(
                TraceCode::COMMISSION_INVOICE_ACTION_QUEUE_DELETE,
                [
                    'job_attempts' => $this->attempts(),
                    'message'      => 'Deleting the job after configured number of tries. Still unsuccessful.'
                ]
            );

            $this->delete();
        }
        else
        {
            $this->release(self::RETRY_INTERVAL);
        }
    }
}
