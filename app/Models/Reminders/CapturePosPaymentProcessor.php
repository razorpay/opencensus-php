<?php


namespace RZP\Models\Reminders;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class CapturePosPaymentProcessor extends ReminderProcessor
{

    function process(string $entity, string $namespace, string $id, array $data)
    {
        $this->trace->info(TraceCode::POS_PAYMENT_REMINDER_CALLBACK,
            [
                'payment_id' => $id,
                'mode'       => $this->mode,
            ]
        );

        try
        {
            (new Payment\Service)->markPosPaymentAsCaptured($id);

        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);

            $this->handleInvalidReminder();
        }

        return ['success' => true];
    }
}
