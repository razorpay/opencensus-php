<?php

namespace RZP\Gateway\Mozart\NetbankingIdbi;


class ReconFields
{
    const BANK                  = 'idbi';
    const PAYMENT_DATE          = 'Payment Date';
    const PAYMENT_ID            = 'Payment Id';
    const PAYMENT_AMOUNT        = 'Payment Amount';
    const BANK_REFERENCE_NUMBER = 'Bank Ref No';
    const PAYMENT_REFERENCE_NUMBER = 'Payment Ref No';

    const ReconFields = [
        self::BANK,
        self::PAYMENT_DATE,
        self::PAYMENT_ID,
        self::PAYMENT_AMOUNT,
        self::PAYMENT_REFERENCE_NUMBER,
        self::BANK_REFERENCE_NUMBER,
    ];
}
