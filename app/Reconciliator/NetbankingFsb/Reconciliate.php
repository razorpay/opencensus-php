<?php

namespace RZP\Reconciliator\NetbankingFsb;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const STATUS                = 'STATUS';
    const AMT                   = 'TransactionAmount';
    const TXNDATE               = 'TRANSACTIONDATE';
    const ACCOUNT_NUMBER        = 'Account_Number';
    const PRN                   = 'AggregatorReferenceNumber';
    const BANK_REFERENCE_NUMBER = 'BankTransactionReferenceNo';


    const PAYMENT_SUCCESS = 'Y';

    public static $columnHeaders = [
        self::PRN,
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
