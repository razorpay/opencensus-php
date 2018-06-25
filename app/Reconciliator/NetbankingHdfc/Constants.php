<?php

namespace RZP\Reconciliator\NetbankingHdfc;

class Constants
{
    const COLUMN_MERCHANT_CODE  = 'merchant_code';

    const COLUMN_CUSTOMER_EMAIL = 'customer_email';

    const COLUMN_CURRENCY       = 'currency';

    const COLUMN_PAYMENT_AMOUNT = 'transaction_amount';

    const COLUMN_FEE            = 'fee';

    const COLUMN_PAYMENT_ID     = 'payment_id';

    const ERROR_CODE            = 'error_code';

    const BANK_PAYMENT_ID       = 'bank_payment_id';

    const COLUMN_PAYMENT_DATE   = 'transaction_date';

    const ERROR_DESCRIPTION     = 'error_description';

    const PAYMENT_COLUMN_HEADERS = [
        self::COLUMN_MERCHANT_CODE,
        self::COLUMN_CUSTOMER_EMAIL,
        self::COLUMN_CURRENCY,
        self::COLUMN_PAYMENT_AMOUNT,
        self::COLUMN_FEE,
        self::COLUMN_PAYMENT_ID,
        self::ERROR_CODE,
        self::BANK_PAYMENT_ID,
        self::COLUMN_PAYMENT_DATE,
        self::ERROR_DESCRIPTION,
    ];
}
