<?php

namespace RZP\Jobs;

use App;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Models\Invoice;
use RZP\Trace\Trace;
use RZP\Trace\TraceCode;

class InvoiceAction extends Job implements ShouldQueue
{
    use InteractsWithQueue;

    const MAX_ALLOWED_ATTEMPTS = 10;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    //
    // Following are the events handled
    //

    const ISSUED     = 'issued';
    const UPDATED    = 'updated';
    const EXPIRED    = 'expired';
    const AUTHORIZED = 'authorized';

    protected $mode;
    protected $event;
    protected $id;

    protected $invoice;
    protected $trace;
    protected $core;
    protected $handler;

    public function __construct(string $mode, string $event, string $id)
    {
        $this->mode  = $mode;
        $this->event = $event;
        $this->id    = $id;
    }

    public function handle()
    {
        try
        {
            $this->init();

            $timeStarted = microtime(true);

            $this->trace->debug(
                TraceCode::INVOICE_ACTION_JOB_RECEIVED,
                $this->getTracePayload());

            $handlerResult = $this->{$this->handler}();

            $this->delete();

            $timeTaken = microtime(true) - $timeStarted;

            $tracePayload = $this->getTracePayload(
                [
                    'time_taken'     => $timeTaken,
                    'handler_result' => $handlerResult,
                ]);

            if ($handlerResult === false)
            {
                $this->trace->error(TraceCode::INVOICE_ACTION_JOB_ERROR, $tracePayload);
            }
            else
            {
                $this->trace->debug(TraceCode::INVOICE_ACTION_JOB_HANDLED, $tracePayload);
            }
        }
        catch (\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    /**
     * - Initializes instance variables: core, trace etc.
     * - Sets application mode, database connection based on the mode.
     * - Validates event
     *
     * @return null
     * @throws LogicException
     */
    private function init()
    {
        $app = App::getFacadeRoot();

        $repo = $app['repo'];

        $this->trace = $app['trace'];

        //
        // Set application mode as well as database connection with given mode.
        //

        $app['rzp.mode'] = $this->mode;

        \Database\DefaultConnection::set($this->mode);

        //
        // Get invoice object
        //

        //
        // This will not return back deleted invoice.
        // But, a deleted invoice will never reach this flow
        // since only a draft invoice can be deleted.
        // We don't perform any queue actions on a draft invoice.
        //
        $this->invoice = $repo->invoice->findOrFail($this->id);

        //
        // Sets handler after validates it too.
        //

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
    }

    // ------------------------- Handlers for various events -------------------------
    //
    // Conventions:
    // - It should be of the following format: handle + Studly cased event constant
    // - The handler method should return boolean and if it's false, it's considered
    //   as error otherwise fine. Also, any exception thrown is considered error too.
    //

    private function handleUpdated()
    {
        $pdfPath = $this->core->createInvoicePdf($this->invoice);

        return (new Invoice\Notifier($this->invoice, $pdfPath))->notifyInvoiceIssuedToCustomer();
    }

    private function handleIssued()
    {
        $pdfPath = $this->core->createInvoicePdf($this->invoice);

        return (new Invoice\Notifier($this->invoice, $pdfPath))->notifyInvoiceIssuedToCustomer();
    }

    private function handleExpired()
    {
        return (new Invoice\Notifier($this->invoice))->notifyInvoiceExpiredToCustomer();
    }

    private function handleAuthorized()
    {
        $this->core->createInvoicePdf($this->invoice);

        //
        // Unless it throws exception, above is assumed to be successful, hence
        // returning true.
        //
        return true;
    }

    // ------------------------------------------------------------

    private function handleException(\Throwable $e)
    {
        //
        // By default job gets deleted
        //

        $jobAction = self::JOB_DELETED;

        if ($this->attempts() > self::MAX_ALLOWED_ATTEMPTS)
        {
            $this->delete();
        }
        else
        {
            $this->release(self::RELEASE_WAIT_SECS);

            $jobAction = self::JOB_RELEASED;
        }

        $this->trace->traceException(
            $e,
            Trace::ERROR,
            TraceCode::INVOICE_ACTION_JOB_ERROR,
            $this->getTracePayload(['job_action' => $jobAction])
        );
    }

    private function getTracePayload(array $with = [])
    {
        $payload = [
            'handler'        => $this->handler,
            'job_attempts'   => $this->attempts(),
            'invoice_id'     => $this->id,
        ];

        //
        // It may not be set when an invalid invoice id is passed.
        //
        if (isset($this->invoice) === true)
        {
            $payload['invoice_status'] = $this->invoice->getStatus();
        }

        return $payload + $with;
    }
}
