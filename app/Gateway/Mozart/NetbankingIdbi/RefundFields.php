<?php

namespace RZP\Gateway\Mozart\NetbankingIdbi;


class RefundFields
{
    const SR_NO               = 'Sr.No.';
    const PAYMENT_ID          = 'Aggreegator Reference No.';
    const TRANSACTION_DATE    = 'Transaction Date';
    const TRANSACTION_AMOUNT  = 'Transaction Amount';
    const REFUND_AMOUNT       = 'Refund Amount';
    const BANK_REFERENCE_ID   = 'Bank Payment ID';

    const REFUND_FIELDS = [
        self::SR_NO,
        self::TRANSACTION_DATE,
        self::PAYMENT_ID,
        self::BANK_REFERENCE_ID,
        self::TRANSACTION_AMOUNT,
        self::REFUND_AMOUNT,
    ];
}
