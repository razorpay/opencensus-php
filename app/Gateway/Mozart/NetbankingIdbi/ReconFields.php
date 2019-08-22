<?php

namespace RZP\Gateway\Mozart\NetbankingIdbi;


class ReconFields
{
    const PAYMENT_DATE          = 'Payment Date';
    const PAYMENT_ID            = 'Payment Id';
    const PAYMENT_AMOUNT        = 'Payment Amount';
    const BANK_REFERENCE_NUMBER = 'Bank Ref No';

    const ReconFields = [
        self::PAYMENT_ID,
        self::PAYMENT_AMOUNT,
        self::BANK_REFERENCE_NUMBER,
        self::PAYMENT_DATE,
    ];
}
