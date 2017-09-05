<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class SettlementIrctc extends Base
{
    protected function processEntry(array & $entry)
    {

        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $amount = $payment->getAmount();

        // We do not capture the payment if its already refunded
        if ($payment->isPartiallyOrFullyRefunded() === false)
        {
            $paymentProcessor->capture($paymentId, ['amount' => $amount]);
        }

        $entry[Batch\Header::STATUS]       = Batch\Status::SUCCESS;
    }
}
