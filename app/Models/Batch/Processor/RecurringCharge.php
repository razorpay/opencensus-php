<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch\Header;
use RZP\Models\Batch\Helpers\RecurringCharge as Helper;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class RecurringCharge extends Base
{
    const RESPONSE_PAYMENT_ID = 'razorpay_payment_id';

    protected function processEntry(array & $entry)
    {
        $paymentProcessor = (new PaymentProcessor($this->merchant));

        $recurringChargeRequestArray = Helper::getRecurringChargeInput($entry);

        $response = $paymentProcessor->process($recurringChargeRequestArray);

        $entry[Header::STATUS] = Batch\Status::SUCCESS;

        $entry[Header::RECURRING_CHARGE_PAYMENT_ID] = $response[self::RESPONSE_PAYMENT_ID];
    }

    protected function sendProcessedMail()
    {
        // Don't send an email
        return;
    }
}
