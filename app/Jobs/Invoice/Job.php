<?php

namespace RZP\Jobs\Invoice;

use RZP\Models\Invoice;
use RZP\Trace\TraceCode;
use RZP\Error\ErrorCode;
use RZP\Jobs\Job as BaseJob;
use RZP\Models\Invoice\Entity;
use RZP\Exception\LogicException;
use RZP\Exception\BadRequestValidationFailureException;

/**
 * Handles asynchronous action against particular invoice, Eg:
 * - Generate PDFs,
 * - Communications - send SMSes, emails etc.
 */
class Job extends BaseJob
{
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
    const CAPTURED              = 'captured';

    /**
     * Custom job name
     *
     * @var string
     */
    protected $jobName = 'invoice_action';

    /**
     * {@inheritDoc}
     */
    protected $queueConfigKey = 'invoice';

    protected $event;
    protected $id;

    protected $invoice;
    protected $core;

    protected $invoiceData;

    public $timeout = 3600;

    public function __construct(string $mode, string $event, string $id, array $invoiceData = [])
    {
        parent::__construct($mode);

        $this->event = $event;
        $this->id    = $id;
        $this->invoiceData = $invoiceData;
    }

    public function getEvent(): string
    {
        return $this->event;
    }

    public function getId(): string
    {
        return $this->id;
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

            $this->invoice = $this->repoManager->invoice->findOrFailPublicWithRelations($this->id, ['entity']);

            $handlerResult = $this->{$handler}();

            $timeTaken = microtime(true) - $timeStarted;

            $this->trace->debug(
                TraceCode::INVOICE_ACTION_JOB_HANDLED,
                $this->getTracePayload(
                    [
                        'time_taken_ms'  => $timeTaken,
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
        $pdfPath = $this->core->createInvoicePdfAndGetFilePath($this->invoice);

        $notifier = new Invoice\Notifier($this->invoice, $pdfPath);

        $customerNotified = true;

        if(isset($this->invoiceData[Entity::REMINDER_ENABLE]) === false)
        {
            $customerNotified = $notifier->notifyInvoiceIssuedToCustomer();
        }

        $reminderCreated = $notifier->createOrUpdateReminder();

        return ($customerNotified and $reminderCreated);
    }

    protected function handleIssued()
    {
        $pdfPath = $this->core->createInvoicePdfAndGetFilePath($this->invoice);

        $notifier = new Invoice\Notifier($this->invoice, $pdfPath);

        $customerNotified = $notifier->notifyInvoiceIssuedToCustomer();

        $reminderCreated = $notifier->createOrUpdateReminder();

        return ($customerNotified and $reminderCreated);
    }

    protected function handleExpired()
    {
        return (new Invoice\Notifier($this->invoice))
                    ->notifyInvoiceExpiredToCustomer();
    }

    protected function handleCaptured()
    {
        // Updates the invoice's pdf version after payment is done
        $this->core->createInvoicePdf($this->invoice);

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
