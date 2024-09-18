<?php


namespace RZP\Jobs\Kafka;

use Illuminate\Database\UniqueConstraintViolationException;
use Razorpay\Spine\Exception\DbQueryException;
use Razorpay\Trace\Logger;

use RZP\Constants\Entity as EntityConstants;
use RZP\Exception\BadRequestException;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\ExtraFieldsException;
use RZP\Models\Feature;
use RZP\Models\QrCode\NonVirtualAccountQrCode;
use RZP\Trace\TraceCode;

class PosMerchantActivationEventsJob extends Job
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
            'job'          => 'PosMerchantActivationEventsJob'
        ];

        $this->trace->info(TraceCode::POS_MERCHANT_ACTIVATION_PAYLOAD, $tracePayload);

        try
        {

            (new NonVirtualAccountQrCode\Service)->addPosQrCodeFeaturesOnPosActivation($this->payload);
        }
        catch (ExtraFieldsException|BadRequestValidationFailureException|BadRequestException|UniqueConstraintViolationException $e)
        {
            //Not propagating the error post this, we don't want to re-attempt here
            $this->trace->warning(TraceCode::POS_MERCHANT_ACTIVATION_WARNING, [
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
                TraceCode::POS_MERCHANT_ACTIVATION_ERROR,
                $payload);

        }

    }
}
