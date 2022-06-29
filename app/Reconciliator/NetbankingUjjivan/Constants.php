<?php

namespace RZP\Reconciliator\NetbankingUjjivan;

class Constants
{
    const PAYMENT_ID        = 'PRN';
    const BANK_PAYMENT_ID   = 'BID';
    const PAYMENT_AMT       = 'AMT';
    const PAYMENT_STATUS    = 'STATUS';
    const PAYMENT_TXNDATE   = 'TXNDATE';
    const ACCOUNT_NUMBER    = 'ACCOUNTNUMBER';

    const TRANSACTION_SUCCESS = 'Y';

    const PAYMENT_COLUMN_HEADERS = [
        self::PAYMENT_ID,
        self::BANK_PAYMENT_ID,
        self::PAYMENT_AMT,
        self::PAYMENT_STATUS,
        self::PAYMENT_TXNDATE,
        self::ACCOUNT_NUMBER,
    ];
}
