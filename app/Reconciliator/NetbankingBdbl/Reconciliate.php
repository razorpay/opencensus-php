<?php

namespace RZP\Reconciliator\NetbankingBdbl;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const MERCHANT_REFERENCE_NO = 'MerchantReferenceNumber';
    const BANK_REFERENCE_NUMBER = 'BankTransactionReferenceNo';
    const AMT                   = 'TransactionAmount';
    const STATUS                = 'STATUS';
    const TXNDATE               = 'TransactionDate';
    const ACCOUNT_NUMBER        = 'Account_Number';

    const PAYMENT_SUCCESS = '1';

    public static $columnHeaders = [
        self::MERCHANT_REFERENCE_NO,
        self::BANK_REFERENCE_NUMBER,
        self::AMT,
        self::STATUS,
        self::TXNDATE,
        self::ACCOUNT_NUMBER,
    ];

    public function getColumnHeadersForType($type)
    {
        return self::$columnHeaders;
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDelimiter()
    {
        return '^';
    }
}
