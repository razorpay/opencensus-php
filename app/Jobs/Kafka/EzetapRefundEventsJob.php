<?php


namespace RZP\Jobs\Kafka;


use RZP\Trace\TraceCode;
use Razorpay\Trace\Logger;
use RZP\Models\Payment\Refund;
use RZP\Exception\BadRequestException;
use RZP\Exception\ExtraFieldsException;
use RZP\Exception\BadRequestValidationFailureException;
use Illuminate\Database\UniqueConstraintViolationException;

class EzetapRefundEventsJob extends Job
{

    /**
     * @throws \Exception
     */
    public function handle()
    {
        $taskId = gen_uuid();

        $this->setTaskId($taskId);

        parent::handle();

        $tracePayload = [
            'job_attempts' => $this->attempts(),
            'mode'         => $this->mode,
            'payload'      => $this->getPayload(),
            'task_id'      => $taskId,
            'job'          => 'EzetapRefundEventsJob'
        ];

        $this->trace->info(TraceCode::EZETAP_REFUND_EVENT_PAYLOAD, $tracePayload);

        try
        {

            (new Refund\Service)->dispatchEzetapRefundWebhook($this->payload);
        }
        catch (ExtraFieldsException|BadRequestValidationFailureException|BadRequestException|UniqueConstraintViolationException $e)
        {
            //Not propagating the error post this, we don't want to re-attempt here
            $this->trace->warning(TraceCode::EZETAP_REFUND_EVENT_WARNING, [
                'code'      => $e->getCode(),
                'message'   => $e->getMessage(),
                'payload'   => $this->payload
            ]);


        }
        catch (\Throwable $e)
        {
            //For unknown errors, we want to reattempt until successful, or else lag increases to trigger an alert.

            $payload = ['payload' => $this->payload,
                        'error_block' => "UNKNOWN_ERROR",
                        'attempt'   => $this->attempts()];

            $this->trace->traceException(
                $e,
                Logger::ERROR,
                TraceCode::EZETAP_REFUND_EVENT_ERROR,
                $payload);

        }

    }
}
