<?php

namespace RZP\Gateway\Netbanking\Vijaya;

class ReconFields
{
    const PAYMENT_ID     = 'payment_id';
    const PAYMENT_AMOUNT = 'payment_amount';
    const DATE           = 'date';
    const BANK_REF_NO    = 'bank_ref_no';

    const PAYMENT_COLUMN_HEADERS = [
        self::PAYMENT_ID,
        self::PAYMENT_AMOUNT,
        self::DATE,
        self::BANK_REF_NO,
    ];

    public static function getPaymentColumnHeaders()
    {
        return self::PAYMENT_COLUMN_HEADERS;
    }
}
