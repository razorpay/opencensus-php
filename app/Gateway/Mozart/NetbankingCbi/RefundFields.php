<?php

namespace RZP\Gateway\Mozart\NetbankingCbi;

class RefundFields
{
    const TYPE_OF_TRANSACTION          = 'Type of Transaction';
    const ACCOUNT_NUMBER               = 'Account Number';
    const TRANSACTION_AMOUNT           = 'Transaction Amount';
    const NARRATION_TEXT               = 'Narration Text';
    const REFERENCE_NO                 = 'Reference No';
    const VALUE_DATE                   = 'Value Date';

    const REFUND_FIELDS = [
        self::TYPE_OF_TRANSACTION,
        self::ACCOUNT_NUMBER,
        self::TRANSACTION_AMOUNT,
        self::NARRATION_TEXT,
        self::REFERENCE_NO,
        self::VALUE_DATE,
    ];
}
