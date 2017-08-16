<?php

namespace RZP\Models\Merchant\FileProcessor\Irctc;

use RZP\Models\Merchant\FileProcessor\Base;

class Refund extends Base
{
    const MERCHANT_REFERENCE = 'merchant_reference';
    const REFUND_TYPE        = 'refund_type';
    const REFUND_AMOUNT      = 'refund_amount';
    const PAYMENT_ID         = 'payment_id';
    const CANCELLATION_DATE  = 'cancellation_date';
    const PAYMENT_AMOUNT     = 'payment_amount';
    const CANCELLATION_ID    = 'cancellation_id';

    const HEADERS = [
        self::MERCHANT_REFERENCE,
        self::REFUND_TYPE,
        self::REFUND_AMOUNT,
        self::PAYMENT_ID,
        self::CANCELLATION_DATE,
        self::PAYMENT_AMOUNT,
        self::CANCELLATION_ID,
    ];

    protected function processEntry(array $row)
    {
        $paymentId = $row[self::PAYMENT_ID];

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $refundType = $row[self::REFUND_TYPE];

        if ($refundType === 'R')
        {
            $this->processRTypeRefunds($row, $payment);
        }
        else if ($refundType === 'C')
        {
            $this->processCTypeRefunds($row, $payment);
        }
    }

    protected function processRTypeRefunds($row, $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $input = [
            Refund\Entity::RECEIPT => $row[self::CANCELLATION_ID],
            Refund\Entity::NOTES   => [
                'receipt' => $row[self::CANCELLATION_ID],
                'refund_type' => $row[self::REFUND_TYPE],
            ]
        ];

        $refund = $paymentProcessor->refund($payment, $input);
    }

    protected function processCTypeRefunds($row, $payment)
    {
        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        if ($payment->getStatus() !== 'captured') {
            //TODO throw exception
        }

        $input = [
            Refund\Entity::AMOUNT  => $row[self::AMOUNT],
            Refund\Entity::RECEIPT => $row[self::CANCELLATION_ID],
            Refund\Entity::NOTES   => [
                'receipt' => $row[self::CANCELLATION_ID],
                'refund_type' => $row[self::REFUND_TYPE],
            ]
        ];

        $refund = $paymentProcessor->refund($payment, $input);
    }

    public function getHeaders()
    {
        return self::HEADERS;
    }

    public function getDelimiter()
    {
        return '|';
    }
}
