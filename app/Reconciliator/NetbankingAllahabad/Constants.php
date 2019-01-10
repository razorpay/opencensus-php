<?php

namespace RZP\Reconciliator\NetbankingAllahabad;

class Constants
{
    const BANK_CODE           = 'bank_code';

    const TXN_DATE            = 'txn_date';

    const MERCHANT_NAME       = 'merchant_name';

    const TRNX_AMOUNT         = 'transaction_amount';

    const PGI_REFERENCE_NO    = 'pgi_reference_no.';

    const BANK_REFERENCE_NO   = 'bank_reference_no.';

    const PAYMENT_COLUMN_HEADERS = [
        self::BANK_CODE,
        self::TXN_DATE,
        self::MERCHANT_NAME,
        self::TRNX_AMOUNT,
        self::PGI_REFERENCE_NO,
        self::BANK_REFERENCE_NO,
    ];
}
