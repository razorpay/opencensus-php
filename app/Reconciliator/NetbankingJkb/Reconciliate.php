<?php

namespace RZP\Reconciliator\NetbankingJkb;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    const BANK_REFERENCE_NUMBER = 'bank_reference_number';
    const PAYMENT_ID            = 'payment_id';
    const AMOUNT                = 'amount';
    const CURRENCY              = 'currency';
    const TIMESTAMP             = 'timestamp';
    const STATUS                = 'status';
    const REAL                  = 'real';
    const ITC                   = 'itc';

    protected $columnHeaders = [
        self::BANK_REFERENCE_NUMBER,
        self::PAYMENT_ID,
        self::AMOUNT,
        self::CURRENCY,
        self::TIMESTAMP,
        self::STATUS,
        self::REAL,
        self::ITC,
    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    public function getDelimiter()
    {
        return '|';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
