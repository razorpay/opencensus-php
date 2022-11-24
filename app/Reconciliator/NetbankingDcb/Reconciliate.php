<?php

namespace RZP\Reconciliator\NetbankingDcb;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const PAYMENT_ID            = 'consumer_code';
    const BANK_REFERENCE_NUMBER = 'req_id';
    const PAYMENT_AMOUNT        = 'total_entry_amt';
    const PAYMENT_STATUS        = 'response_code';
    const PAYMENT_DATE          = 'r_cre_time';

    const PAYMENT_SUCCESS = '000';

    public static $columnHeaders = [
        self::PAYMENT_ID,
        self::BANK_REFERENCE_NUMBER,
        self::PAYMENT_AMOUNT,
        self::PAYMENT_STATUS,
        self::PAYMENT_DATE,
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
