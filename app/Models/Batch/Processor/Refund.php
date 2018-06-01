<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Refund extends Base
{
    protected function processEntry(array & $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        /** @var Payment\Entity $payment */
        $payment = $this->repo->payment->findByPublicIdAndMerchant(
                                            $paymentId,
                                            $this->merchant);

        $paymentProcessor = (new PaymentProcessor($this->merchant));

        $input = [
            Payment\Refund\Entity::AMOUNT => (string) $entry[Batch\Header::AMOUNT],
            Payment\Refund\Entity::NOTES  => $entry[Batch\Header::NOTES] ?? [],
        ];

        $refund = $paymentProcessor->refundPaymentViaBatchEntry($payment, $this->batch, $input);

        // Update the entry with output values
        $entry[Batch\Header::STATUS]          = Batch\Status::SUCCESS;
        $entry[Batch\Header::REFUND_ID]       = $refund->getPublicId();
        $entry[Batch\Header::REFUNDED_AMOUNT] = $refund->getAmount();
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
                $processedAmount += $entry[Batch\Header::REFUNDED_AMOUNT];
            }
        }

        $this->batch->setProcessedAmount($processedAmount);
    }
}
