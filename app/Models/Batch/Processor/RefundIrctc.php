<?php

namespace RZP\Models\Batch\Processor;

use RZP\Models\Batch;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class RefundIrctc extends Base
{
    protected function processEntry(array & $entry)
    {
        $paymentId = trim($entry[Batch\Header::PAYMENT_ID]);

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $type = $entry[Batch\Header::REFUND_TYPE];

        $processor = 'process' . studly_case($type) .'TypeRefunds';

        $this->$processor($row, $payment);

        $entry[Batch\Header::STATUS]       = Batch\Status::SUCCESS;
        $entry[Batch\Header::REFUND_ID]    = $refund->getPublicId();
        $entry[Batch\Header::REFUND_DATE]  = $refund->getCreatedAt();
        $enrty[Batch\Header::PAYMENT_DATE] = $payment->getCreatedAt();
    }

    protected function processRTypeRefunds(array $row, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $input = [
            Entity::RECEIPT => $row[self::CANCELLATION_ID . '_' . self::MERCHANT_REFERENCE],
            Entity::NOTES   => [
                'reservation_id'  => $row[self::MERCHANT_REFERENCE],
                'cancellation_id' => $row[self::CANCELLATION_ID],
                'refund_type'     => $row[self::REFUND_TYPE],
            ],
        ];

        $paymentProcessor->createRefundFromMerchantFile($payment, $input);
    }

    protected function processCTypeRefunds(array $row, Payment\Entity $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        if ($payment->getStatus() !== Payment\Status::CAPTURED) {
            throw new Exception\BadRequestException(
                'Payment is not Captured for C type refund',
                $row
            );
        }

        $input = [
            Entity::AMOUNT  => intval($row[self::REFUND_AMOUNT] * 100),
            Entity::RECEIPT => $row[self::CANCELLATION_ID . '_' . self::MERCHANT_REFERENCE],
            Entity::NOTES   => [
                'reservation_id'  => $row[self::MERCHANT_REFERENCE],
                'cancellation_id' => $row[self::CANCELLATION_ID],
                'refund_type'     => $row[self::REFUND_TYPE],
            ],
        ];

        $paymentProcessor->createRefundFromMerchantFile($payment, $input);
    }
}
