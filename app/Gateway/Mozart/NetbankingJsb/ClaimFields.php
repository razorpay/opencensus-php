<?php

namespace RZP\Gateway\Mozart\NetbankingJsb;

class ClaimFields
{
    const PAYMENT_ID            = 'MerRefNo';
    const BANK_REFERENCE_NUMBER = 'Reference_tag';
    const CURRENCY              = 'Currency';
    const PAYMENT_AMOUNT        = 'payment_amount';
    const STATUS                = 'payment_status';
    const TRANSACTION_DATE      = 'date';
    const MERCHANT_CODE         = 'AGG_CODE';
    const MERCHANT_NAME         = 'AGGREGATOR_NAME';

    const CLAIM_FIELDS = [
        self::PAYMENT_ID,
        self::BANK_REFERENCE_NUMBER,
        self::CURRENCY,
        self::PAYMENT_AMOUNT,
        self::STATUS,
        self::TRANSACTION_DATE,
        self::MERCHANT_CODE,
        self::MERCHANT_NAME
    ];
}
