<?php

namespace RZP\Reconciliator\NetbankingUco;

use phpseclib\Crypt\AES;
use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{

    const MERCHANT_REFERENCE_NO = 'PG Payment Reference No(PRN)';
    const BANK_REFERENCE_NUMBER = 'Bank Payment Reference No';
    const AMT                   = 'Amount';
    const STATUS                = 'Transaction Status';
    const TXNDATE               = 'Transaction Date(DD-MM-YYYY)';
    const ACCOUNT_NUMBER        = 'Account_No';

    const PAYMENT_SUCCESS = 'S';

    public static $columnHeaders = [
        self::ACCOUNT_NUMBER,
        self::MERCHANT_REFERENCE_NO,
        self::BANK_REFERENCE_NUMBER,
        self::AMT,
        self::STATUS,
        self::TXNDATE,
    ];

    public function getColumnHeadersForType($type)
    {
        return self::$columnHeaders;
    }

    public function getDelimiter()
    {
        return '^';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
