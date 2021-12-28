<?php

namespace RZP\Reconciliator\NetbankingTmb;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const MERCHANT_CODE         = 'Merchant Code';
    const MERCHANT_TRN          = 'Merchant TRN';
    const TRANSACTION_AMOUNT    = 'Transaction Amount';
    const PAYMENT_REMARKS       = 'Payment Remarks';
    const BANK_REFERENCE_NO     = 'Bank Reference No';
    const TXN_DATE_TIME         = 'Txn Date Time';
    const RESPONSE_MESSAGE      = 'Response Message';


    const PAYMENT_SUCCESS = 'Y';

    public static $columnHeaders = [
        self::MERCHANT_CODE,
        self::MERCHANT_TRN,
        self::TRANSACTION_AMOUNT,
        self::PAYMENT_REMARKS,
        self::BANK_REFERENCE_NO,
        self::TXN_DATE_TIME,
        SELF::RESPONSE_MESSAGE,
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
        return ',';
    }
}
