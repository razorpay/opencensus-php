<?php

namespace RZP\Models\Merchant\FileProcessor\Irctc;

use RZP\Models\Merchant\FileProcessor\TypeProcessor;
use RZP\Models\Payment\Processor\Processor as PaymentProcessor;

class Settlement extends TypeProcessor
{
    const PAYMENT_ID         = 'payment_id';
    const PAYMENT_AMOUNT     = 'payment_amount';
    const TRANSACTION_DATE   = 'transaction_date';
    const MERCHANT_REFERENCE = 'merchant_reference';

    const HEADERS = [
        self::PAYMENT_ID,
        self::PAYMENT_AMOUNT,
        self::TRANSACTION_DATE,
        self::MERCHANT_REFERENCE,
    ];

    protected function processEntry(array $row)
    {
        $paymentId = $row[self::PAYMENT_ID];

        $payment = $this->repo->payment->findByPublicId($paymentId);

        $paymentProcessor = (new PaymentProcessor($payment->merchant));

        $amount = $payment->getAmount();

        // We do not capture the payment if its already refunded
        if ($payment->isPartiallyOrFullyRefunded() === false)
        {
            $paymentProcessor->capture($paymentId, ['amount' => $amount]);
        }
    }

    public function getHeaders()
    {
        return self::HEADERS;
    }

    public function getDelimiter()
    {
        return '|';
    }

    protected function getId(array $entry)
    {
        return $entry[self::PAYMENT_ID];
    }
}
