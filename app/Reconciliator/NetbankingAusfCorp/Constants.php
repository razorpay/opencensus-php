<?php

namespace RZP\Reconciliator\NetbankingAusfCorp;

class Constants
{
    const TRANSACTION_TYPE          = 'TRANSACTION TYPE';
    const CHANNEL_REF_NO            = 'CHANNEL_REF_NO';
    const PAYMENT_ID_EXT            = 'PAYMENT_ID_EXT';
    const MERCHANT_ID               = 'MERCHANT_ID';
    const USERREFERENCENO           = 'USERREFERENCENO';
    const HOST_REF_NO               = 'HOST_REF_NO';
    const EXTERNALREFERENCEID_EXT   = 'EXTERNALREFERENCEID_EXT';
    const PAYMENT_DATE              =  'PAYMENT_DATE';
    const PAYMENT_AMT               = 'PAYMENT_AMT';
    const REFUND_AMOUNT             = 'REFUND_AMOUNT';
    const DEBIT_ACCOUNT_NO          = 'DEBIT_ACCOUNT_NO';
    const STATUS                    = 'STATUS';
    const MERCHANT_ACCT_NO          = 'MERCHANT_ACCT_NO';
    const MERCHANT_URL              = 'MERCHANT_URL';

    const Date                      = 'Date';

    const AMOUNT                    = 'AMOUNT';

    const PAYMENT_ID                = 'PAYMENT_ID';

    const PAYMENT_SUCCESS           = 'PAID';

    const MERCHANT_CODE             = 'MERCHANT_CODE';

    const TRANSACTION_DATE          = 'TRANSACTION_DATE';

    const DEBIT_ACCOUNT_ID          = 'DEBIT_ACCOUNT_ID';

    const EXTERNALREFERENCEID       = 'EXTERNALREFERENCEID';

    const SERVICE_CHARGES_AMOUNT    = 'SERVICE_CHARGES_AMOUNT';

    const MERCHANT_ACCOUNT_NUMBER   = 'MERCHANT_ACCOUNT_NUMBER';

    const PAYMENT_COLUMN_HEADERS = [
        self::TRANSACTION_TYPE,
        self::CHANNEL_REF_NO,
        self::PAYMENT_ID_EXT,
        self::MERCHANT_ID,
        self::USERREFERENCENO,
        self::HOST_REF_NO,
        self::EXTERNALREFERENCEID_EXT,
        self::PAYMENT_DATE,
        self::PAYMENT_AMT,
        self::REFUND_AMOUNT,
        self::DEBIT_ACCOUNT_NO,
        self::STATUS,
        self::MERCHANT_ACCT_NO,
        self::MERCHANT_URL,
    ];
}
