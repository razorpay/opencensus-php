<?php

namespace RZP\Gateway\Mozart\NetbankingCub;


class RefundFields
{
    const PAYMENT_ID          = 'Biller Reference No';
    const TRANSACTION_DATE    = 'Transaction Date';
    const TRANSACTION_AMOUNT  = 'Transaction Amount';
    const REFUND_AMOUNT       = 'Refund Amount';
    const BANK_REFERENCE_ID   = 'Bank Reference No';
    const TYPE_IDENTIFICATION = 'Identifications of Cancel Refund';

    const REFUND_FIELDS = [
        self::BANK_REFERENCE_ID,
        self::PAYMENT_ID,
        self::TRANSACTION_AMOUNT,
        self::TRANSACTION_DATE,
        self::REFUND_AMOUNT,
        self::TYPE_IDENTIFICATION,
    ];
}
