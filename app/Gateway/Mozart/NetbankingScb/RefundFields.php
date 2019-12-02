<?php

namespace RZP\Gateway\Mozart\NetbankingScb;

class RefundFields
{
    const SR_NO                 = 'Sr.No.';
    const TRANSACTION_DATE      = 'Transaction Date';
    const REFUND_DATE           = 'Refund Date';
    const BANK_REFERENCE_NUMBER = 'Bank Ref No.';
    const PAYMENT_ID            = 'Payment Id';
    const PAYMENT_AMOUNT        = 'Txn Amount';
    const REFUND_AMOUNT         = 'Refund Amount';

    const REFUND_FIELDS = [
        self::SR_NO,
        self::TRANSACTION_DATE,
        self::REFUND_DATE,
        self::BANK_REFERENCE_NUMBER,
        self::PAYMENT_ID,
        self::PAYMENT_AMOUNT,
        self::REFUND_AMOUNT,
    ];
}
