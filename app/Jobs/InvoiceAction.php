<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Models\Invoice;
use RZP\Trace\TraceCode;

class InvoiceAction extends Job implements ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    const MAX_ALLOWED_ATTEMPTS = 10;
    const RELEASE_WAIT_SECS    = 60;

    protected $event;
    protected $invoice;

    protected $core;

    public function __construct($event, Invoice\Entity $invoice)
    {
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

    protected function init()
    {
        $this->app   = App::getFacadeRoot();
        $this->trace = $this->app['trace'];

        $this->handler = 'handle' . studly_case($this->event);

        if (method_exists($this, $this->handler) === false)
        {
            throw new \LogicException("InvoiceAction: Handler - $this->handler not found.");
        }


        $this->core = new Invoice\Core;
    }

    protected function handleException(\Throwable $e)
    {
        $this->trace->traceException($e);
        $this->trace->error(TraceCode::INVOICE_ACTION_JOB_ERROR, $this->getTracePayload());

        if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);
        }
    }

    protected function getTracePayload(array $with = [])
    {
        $payload = [
            'invoice_id'     => $this->invoice->getId(),
            'invoice_status' => $this->invoice->getStatus(),
            'handler'        => $this->handler,
            'job_attempts'   => $this->attempts(),
        ];

        return $payload + $with;
    }

    protected function handleIssued()
    {
        (new Invoice\PdfGenerator($this->invoice))->generate();

        (new Invoice\Notifier($this->invoice))->notifyInvoiceIssuedToCustomer();
    }

    protected function handleExpired()
    {
        (new Invoice\Notifier($this->invoice))->notifyInvoiceExpiredToCustomer();
    }
}
