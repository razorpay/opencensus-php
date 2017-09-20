<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class IrctcSettlement extends Base
{
    protected function processEntry(array & $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $params = [
            Payment\Entity::AMOUNT => $payment->getAmount(),
            Payment\Entity::CURRENCY => $payment->getCurrency()
        ];

        // We do not capture the payment if its already refunded
        if (($payment->isPartiallyOrFullyRefunded() === false) and
            ($payment->hasBeenCaptured() === false))
        {
            $paymentProcessor->capture($payment, $params);
        }

        $entry[Batch\Header::STATUS] = Batch\Status::SUCCESS;
    }

    /**
     * Besides what parent's method does:
     * - Sets aggregate processed amount of batch entity.
     *
     * @param $entries
     */
    protected function postProcessEntries(array & $entries)
    {
        parent::postProcessEntries($entries);

        $processedAmount = 0;

        foreach ($entries as $entry)
        {
            if ($entry[Batch\Header::STATUS] === Batch\Status::SUCCESS)
            {
                $processedAmount += $entry[Batch\Header::PAYMENT_AMOUNT];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);
    }
}
