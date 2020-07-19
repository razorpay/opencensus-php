<?php

namespace RZP\Models\Reminders;

use RZP\Models\Payment;
use RZP\Models\Payment\UpiMetadata;

class UpiAutoRecurringReminderProcessor extends ReminderProcessor
{
    public function process(string $entity, string $namespace, string $id, array $data)
    {
        $payment = $this->retrievePayment($id);
        $metadata = $payment->getUpiMetadata();

        if ($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_PRE_DEBIT))
        {
            $processor = (new Payment\Processor\Processor($payment->merchant));
            $processor->setPayment($payment);

            $processor->processAutoRecurringPreDebitForUpi($payment);
        }
        else if ($metadata->isInternalStatus(UpiMetadata\InternalStatus::REMINDER_IN_PROGRESS_FOR_AUTHORIZE))
        {
            $processor = (new Payment\Processor\Processor($payment->merchant));
            $processor->setPayment($payment);

            $processor->processAutoRecurringAuthorizeForUpi($payment);
        }
        else
        {
            $this->handleInvalidReminder();
        }

        return ['success' => true];
    }

    protected function retrievePayment($id): Payment\Entity
    {
        return (new Payment\Core)->retrievePaymentById($id);
    }
}
