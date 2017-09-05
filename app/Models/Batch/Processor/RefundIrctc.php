<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Payment;
use RZP\Models\Payment\Refund;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class RefundIrctc extends Base
{
    protected function processEntry(array & $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $type = $entry[Batch\Header::REFUND_TYPE];

        $processor = 'process' . studly_case($type) .'TypeRefunds';

        $refund = $this->$processor($entry, $payment);

        $entry[Batch\Header::STATUS]       = Batch\Status::SUCCESS;
        $entry[Batch\Header::REFUND_ID]    = $refund->getPublicId();
        $entry[Batch\Header::REFUND_DATE]  = $refund->getCreatedAt();
        $enrty[Batch\Header::PAYMENT_DATE] = $payment->getCreatedAt();
    }

    protected function processRTypeRefunds(array $entry, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $input = [
            Refund\Entity::RECEIPT => $entry[Batch\Header::CANCELLATION_ID . '_' . Batch\Header::MERCHANT_REFERENCE],
            Refund\Entity::NOTES   => [
                'reservation_id'  => $entry[Batch\Header::MERCHANT_REFERENCE],
                'cancellation_id' => $entry[Batch\Header::CANCELLATION_ID],
                'refund_type'     => $entry[Batch\Header::REFUND_TYPE],
            ],
        ];

        return $paymentProcessor->createRefundFromMerchantFile($payment, $input, $this->batch);
    }

    protected function processCTypeRefunds(array $entry, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        // In case the payment is not captured, we need to capture the payment before initiating the refund
        if ($payment->isCaptured() === false)
        {
            $params = [
                Payment\Entity::AMOUNT => intval($entry[Batch\Header::PAYMENT_AMOUNT] * 100)
            ];

            $paymentProcessor->capture($payment, $params);
        }

        $input = [
            Refund\Entity::AMOUNT  => intval($entry[Batch\Header::REFUND_AMOUNT] * 100),
            Refund\Entity::RECEIPT => $entry[Batch\Header::CANCELLATION_ID . '_' . Batch\Header::MERCHANT_REFERENCE],
            Refund\Entity::NOTES   => [
                'reservation_id'  => $entry[Batch\Header::MERCHANT_REFERENCE],
                'cancellation_id' => $entry[Batch\Header::CANCELLATION_ID],
                'refund_type'     => $entry[Batch\Header::REFUND_TYPE],
            ],
        ];

        return $paymentProcessor->createRefundFromMerchantFile($payment, $input, $this->batch);
    }
}
