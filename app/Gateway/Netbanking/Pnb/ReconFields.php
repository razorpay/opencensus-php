<?php

namespace RZP\Gateway\Netbanking\Pnb;

class ReconFields
{
    const PRN = 'prn';
    const PAYMENT_ID = 'payment_id';
    const BANK_PAYMENT_ID = 'bank_reference';
    const AMOUNT = 'amount';
    const DATE = 'date';

    public static function getPaymentColumnHeaders()
    {
        return [
            self::PRN,
            self::PAYMENT_ID,
            self::BANK_PAYMENT_ID,
            self::AMOUNT,
            self::DATE,
        ];
    }
}
