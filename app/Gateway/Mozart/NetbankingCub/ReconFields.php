<?php

namespace RZP\Gateway\Mozart\NetbankingCub;


class ReconFields
{
    const PAYMENT_ID            = 'Payment Id';
    const PAYMENT_AMOUNT        = 'Payment Amount';
    const BANK_REFERENCE_NUMBER = 'Bank Ref No';
    const PAYMENT_DATE          = 'Payment Date';

    const ReconFields = [
        self::PAYMENT_ID,
        self::PAYMENT_AMOUNT,
        self::BANK_REFERENCE_NUMBER,
        self::PAYMENT_DATE,
    ];
}
