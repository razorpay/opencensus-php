<?php

namespace RZP\Reconciliator\NetbankingCanara;

class Constants
{
    const HEADER_MERCHANTREFRENCE   = 'MERCHANTREFRENCE';

    const COLUMN_MERCHANT_CODE      = 'merchant_code';

    const COLUMN_PAYMENT_AMOUNT     = 'transaction_amount';

    const COLUMN_PAYMENT_ID         = 'payment_id';

    const COLUMN_BANK_PAYMENT_ID    = 'bank_payment_id';

    const COLUMN_PAYMENT_DATE       = 'transaction_date';

    const CUSTOMER_ACCOUNT_NUMBER   = 'customer_account_number';

    const PAYMENT_COLUMN_HEADERS = [
        self::COLUMN_BANK_PAYMENT_ID,
        self::CUSTOMER_ACCOUNT_NUMBER,
        self::COLUMN_PAYMENT_DATE,
        self::COLUMN_PAYMENT_ID,
        self::COLUMN_MERCHANT_CODE,
        self::COLUMN_PAYMENT_AMOUNT,
    ];
}
