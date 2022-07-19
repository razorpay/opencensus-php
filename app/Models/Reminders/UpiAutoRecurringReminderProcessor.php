<?php

namespace RZP\Models\Reminders;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Trace\TraceCode;
use RZP\Models\Payment\UpiMetadata;

class UpiAutoRecurringReminderProcessor extends ReminderProcessor
{
    public function process(string $entity, string $namespace, string $id, array $data)
    {
        $payment = $this->retrievePayment($id);
        $metadata = $payment->getUpiMetadata();
        $waitForAuthReminder = false;
        $processed = false;

        $currentTime = Carbon::now()->addMinutes(1)->getTimestamp();
        // Validation if we receive any extra callback from reminder service and our remind at is in future
        if(($this->mode === 'live') and
            ($metadata->getRemindAt() !== null) and
            ($metadata->getRemindAt() > $currentTime))
        {
            $this->trace->info(
                TraceCode::UPI_RECURRING_REMINDER_SERVICE_TIMEOUT_CALLBACK,
                [
                    'payment'           => $payment->toArrayTraceRelevant(),
                    'metadata'          => $metadata,
                    'current_time'      => $currentTime,
                ]
            );

            return ['success' => true];
        }

        if ($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_PRE_DEBIT))
        {
            $processor = (new Payment\Processor\Processor($payment->merchant));
            $processor->setPayment($payment);

            $processor->processAutoRecurringPreDebitForUpi($payment);
            $processed = true;

            // Now if the next remind at is in future, we need to wait for auth reminder call
            if ($metadata->getRemindAt() > Carbon::now()->getTimestamp())
            {
                $waitForAuthReminder = true;
            }
        }

        // processAutoRecurringPreDebitForUpi can set the remind time to a past or current value,
        // thus without sending a reminder, this condition will pick the same payment for authorization right away
        // Note: The processor will not send a reminder update call if the remind at is in past.
        if (($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE)) and
            ($waitForAuthReminder === false))
        {
            $processor = (new Payment\Processor\Processor($payment->merchant));
            $processor->setPayment($payment);

            $processor->processRecurringDebitForUpi($payment);
            $processed = true;
        }

        return ['success' => $processed];
    }

    protected function retrievePayment($id): Payment\Entity
    {
        return (new Payment\Core)->retrievePaymentById($id);
    }
}
