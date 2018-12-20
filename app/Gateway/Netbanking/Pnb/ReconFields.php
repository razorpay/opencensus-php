<?php

namespace RZP\Gateway\Netbanking\Pnb;

class ReconFields
{
    const BANK_PAYMENT_ID = 'bank transaction id';
    const AMOUNT = 'amount';
    const DATE = 'transaction date';
    const PAYMENT_ID = 'aggregator ref id';

    public static function getPaymentColumnHeaders()
    {
        return [
            self::BANK_PAYMENT_ID,
            self::AMOUNT,
            self::DATE,
            self::PAYMENT_ID,
        ];
    }
}
