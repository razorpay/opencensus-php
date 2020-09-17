<?php

namespace RZP\Gateway\Netbanking\Corporation;

class ReconciliationFields
{
    const MERCHANT_CODE      = 'merchant_code';

    const TXN_EXECUTED_DATE  = 'txn_executed_date';

    const BANK_TXN_ID        = 'bank_txn_id';

    const MERCHANT_TXN_ID    = 'merchant_txn_id';

    const TXN_ORG_AMOUNT     = 'txn_org_amount';

    const STATUS             = 'status';

    const PAYMENT_COLUMN_HEADERS = [
        self::MERCHANT_CODE,
        self::TXN_EXECUTED_DATE,
        self::BANK_TXN_ID,
        self::MERCHANT_TXN_ID,
        self::TXN_ORG_AMOUNT,
        self::STATUS,
    ];

    public static function getPaymentColumnHeaders()
    {
        return self::PAYMENT_COLUMN_HEADERS;
    }
}
