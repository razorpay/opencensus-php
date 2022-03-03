<?php

namespace RZP\Gateway\Mozart\NetbankingCbi;

class RefundFields
{
    const TYPE_OF_TRANSACTION          = 'Type of Transaction';
    const FROM_ACCOUNT_NUMBER          = 'From Account';
    const TO_ACCOUNT_NUMBER            = 'To Account Number';
    const TRANSACTION_AMOUNT           = 'Transaction Amount';
    const NARRATION_TEXT               = 'Narration Text';
    const REFERENCE_NO                 = 'Reference No';

    const REFUND_FIELDS = [
        self::TYPE_OF_TRANSACTION,
        self::FROM_ACCOUNT_NUMBER,
        self::TO_ACCOUNT_NUMBER,
        self::TRANSACTION_AMOUNT,
        self::NARRATION_TEXT,
        self::REFERENCE_NO,
    ];
}
