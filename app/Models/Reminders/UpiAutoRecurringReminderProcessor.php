<?php

namespace RZP\Models\Reminders;

use Carbon\Carbon;
use RZP\Models\Payment;
use RZP\Models\Payment\UpiMetadata;

class UpiAutoRecurringReminderProcessor extends ReminderProcessor
{
    public function process(string $entity, string $namespace, string $id, array $data)
    {
        $payment = $this->retrievePayment($id);
        $metadata = $payment->getUpiMetadata();
        $waitForAuthReminder = false;
        $processed = false;

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

            $processor->processAutoRecurringAuthorizeForUpi($payment);
            $processed = true;
        }

        return ['success' => $processed];
    }

    protected function retrievePayment($id): Payment\Entity
    {
        return (new Payment\Core)->retrievePaymentById($id);
    }
}
