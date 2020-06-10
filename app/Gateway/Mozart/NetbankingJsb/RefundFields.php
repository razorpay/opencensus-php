<?php

namespace RZP\Gateway\Mozart\NetbankingJsb;

class RefundFields
{
    const MERCHANT_CODE         = 'MerCode';
    const MERCHANT_NAME         = 'MerName';
    const PAYMENT_ID            = 'MerRefNo';
    const REFUND_AMOUNT         = 'RefAmt';
    const CURRENCY              = 'CRN';
    const BANK_REFERENCE_NUMBER = 'BankRefNo';
    const TRANSACTION_DATE      = 'TranDate';

    const REFUND_FIELDS = [
        self::MERCHANT_CODE,
        self::MERCHANT_NAME,
        self::PAYMENT_ID,
        self::REFUND_AMOUNT,
        self::CURRENCY,
        self::BANK_REFERENCE_NUMBER,
        self::TRANSACTION_DATE
    ];
}
