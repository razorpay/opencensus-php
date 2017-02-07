<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Invoice;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class InvoiceAction extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    const MAX_ALLOWED_ATTEMPTS = 10;
    const RELEASE_WAIT_SECS    = 60;

    protected $mode;
    protected $event;
    protected $invoice;
    protected $trace;
    protected $core;
    protected $handler;

    public function __construct(string $mode, string $event, Invoice\Entity $invoice)
    {
        $this->mode    = $mode;

        $this->event   = $event;

        $this->invoice = $invoice;
    }

    public function handle()
    {
        try
        {
            $this->init();

            $timeStarted = microtime(true);

            $this->trace->debug(TraceCode::INVOICE_ACTION_JOB_RECEIVED, $this->getTracePayload());

            $this->{$this->handler}();

            $this->delete();

            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(TraceCode::INVOICE_ACTION_JOB_HANDLED, $this->getTracePayload(['time_taken' => $timeTaken]));
        }
        catch (\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    private function init()
    {
        \Database\DefaultConnection::set($this->mode);

        $app = App::getFacadeRoot();
        $this->trace = $app['trace'];

        $this->handler = 'handle' . studly_case($this->event);

        if (method_exists($this, $this->handler) === false)
        {
            throw new LogicException(
                "InvoiceAction: Handler - $this->handler not found.",
                ErrorCode::SERVER_ERROR_MISSING_HANDLER,
                [
                    'invoice_id' => $this->invoice->getId(),
                ]);
        }

        $this->core = new Invoice\Core;

        $this->core->setMode($this->mode);
    }

    //
    // Handlers for various events
    //

    private function handleIssued()
    {
        $pdfPath = $this->core->getInvoicePdf($this->invoice);

        (new Invoice\Notifier($this->invoice, $pdfPath))->notifyInvoiceIssuedToCustomer();
    }

    private function handleExpired()
    {
        (new Invoice\Notifier($this->invoice))->notifyInvoiceExpiredToCustomer();
    }

    // ------------------------------------------------------------

    private function handleException(\Throwable $e)
    {
        $this->trace->traceException(
            $e,
            Trace::ERROR,
            TraceCode::INVOICE_ACTION_JOB_ERROR,
            $this->getTracePayload()
        );

        if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);
        }
    }

    private function getTracePayload(array $with = [])
    {
        $payload = [
            'invoice_id'     => $this->invoice->getId(),
            'invoice_status' => $this->invoice->getStatus(),
            'handler'        => $this->handler,
            'job_attempts'   => $this->attempts(),
        ];

        return $payload + $with;
    }
}
