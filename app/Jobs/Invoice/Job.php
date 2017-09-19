<?php

namespace RZP\Jobs\Invoice;

use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use RZP\Jobs\Job as BaseJob;
use RZP\Models\Invoice;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Handles asynchronous action against particular invoice, Eg:
 * - Generate PDFs,
 * - Communications - send SMSes, emails etc.
 */
class Job extends BaseJob implements ShouldQueue
{
    use InteractsWithQueue;

    const MAX_ALLOWED_ATTEMPTS = 10;
    const RELEASE_WAIT_SECS    = 60;

    const JOB_DELETED          = 'job_deleted';
    const JOB_RELEASED         = 'job_released';

    //
    // Following are the events handled
    //

    const ISSUED                = 'issued';
    const UPDATED               = 'updated';
    const EXPIRED               = 'expired';
    const AUTHORIZED            = 'authorized';

    protected $event;
    protected $id;

    protected $invoice;
    protected $core;

    public function __construct(string $mode, string $event, string $id)
    {
        parent::__construct($mode);

        $this->event = $event;
        $this->id    = $id;
    }

    public function handle()
    {
        parent::handle();

        $this->core = new Invoice\Core;

        try
        {
            $this->trace->debug(
                TraceCode::INVOICE_ACTION_JOB_RECEIVED,
                $this->getTracePayload());

            $timeStarted = microtime(true);

            $handler = $this->getHandlerForJobEvent();

            $this->invoice = $this->repoManager->invoice->findOrFail($this->id);

            $handlerResult = $this->{$handler}();

            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(
                TraceCode::INVOICE_ACTION_JOB_HANDLED,
                $this->getTracePayload(
                    [
                        'time_taken'     => $timeTaken,
                        'handler_result' => $handlerResult,
                    ]));

            $this->delete();
        }
        catch (\Throwable $e)
        {
            $this->handleException($e);
        }
    }

    /**
     * Gets handler for the job event
     *
     * @return string
     *
     * @throws LogicException
     */
    protected function getHandlerForJobEvent(): string
    {
        $handler = 'handle' . studly_case($this->event);

        if (method_exists($this, $handler) === false)
        {
            $error = "InvoiceAction: Handler - $handler not found.";

            throw new LogicException(
                $error,
                ErrorCode::SERVER_ERROR_MISSING_HANDLER,
                $this->getTracePayload());
        }

        return $handler;
    }

    // ------------------------- Handlers for various events ---------
    //
    // Conventions:
    // - It should be of the following format: handle + Studly cased event constant
    // - The handler method should return boolean and if it's false, it's considered
    //   as error otherwise fine. Also, any exception thrown is considered error too.
    //

    protected function handleUpdated()
    {
        $pdfPath = $this->core->createInvoicePdf($this->invoice);

        return (new Invoice\Notifier($this->invoice, $pdfPath))
                    ->notifyInvoiceIssuedToCustomer();
    }

    protected function handleIssued()
    {
        $pdfPath = $this->core->createInvoicePdf($this->invoice);

        return (new Invoice\Notifier($this->invoice, $pdfPath))
                    ->notifyInvoiceIssuedToCustomer();
    }

    protected function handleExpired()
    {
        return (new Invoice\Notifier($this->invoice))
                    ->notifyInvoiceExpiredToCustomer();
    }

    protected function handleAuthorized()
    {
        $this->core->createInvoicePdf($this->invoice);

        // Unless it throws exception, above is assumed to be successful, hence
        // returning true.

        return true;
    }

    // ---------------------------------------------------------------

    protected function handleException(\Throwable $e)
    {
        // By default job gets deleted

        $jobAction = self::JOB_DELETED;

        // Delete the job if max attempt has exhausted or
        // if it's bad request validation exception (which will
        // never get corrected on subsequent retries also).

        if (($this->attempts() >= self::MAX_ALLOWED_ATTEMPTS) or
            ($e instanceof BadRequestValidationFailureException))
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
            null,
            TraceCode::INVOICE_ACTION_JOB_ERROR,
            $this->getTracePayload(['job_action' => $jobAction])
        );
    }

    protected function getTracePayload(array $with = [])
    {
        $payload = [
            'job_attempts' => $this->attempts(),
            'invoice_id'   => $this->id,
            'mode'         => $this->mode,
            'event'        => $this->event,
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
