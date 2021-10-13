<?php


namespace RZP\Models\Reminders;

use RZP\Models\Payment;
use RZP\Trace\TraceCode;

class CoDPaymentPendingProcessor extends ReminderProcessor
{

    function process(string $entity, string $namespace, string $id, array $data)
    {
        $this->trace->info(TraceCode::COD_PAYMENT_PENDING_REMINDER_CALLBACK,
            [
                'payment_id' => $id,
                'mode'       => $this->mode,
            ]
        );

        try
        {
            $shouldContinue = (new Payment\Service)->handleReminderCallbackToTimeoutCoDPayment($id);

            if ($shouldContinue === false)
            {
                $this->handleInvalidReminder();
            }

        }
        catch (\Exception $exception)
        {
            $this->trace->traceException($exception);

            $this->handleInvalidReminder();
        }

        return ['success' => true];
    }
}