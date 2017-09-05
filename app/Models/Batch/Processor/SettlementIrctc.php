<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class SettlementIrctc extends Base
{
    protected function processEntry(array & $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $params = [
            Payment\Entity::AMOUNT => intval($entry[Batch\Header::PAYMENT_AMOUNT] * 100)
        ];

        // We do not capture the payment if its already refunded
        if ($payment->isPartiallyOrFullyRefunded() === false)
        {
            $paymentProcessor->capture($payment, $params);
        }

        $entry[Batch\Header::STATUS]       = Batch\Status::SUCCESS;
    }
}
