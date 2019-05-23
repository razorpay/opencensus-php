<?php

namespace RZP\Reconciliator\NetbankingSib;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\NetbankingSib\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        ReconFields::TRANSACTION_DATE,
        ReconFields::PAYMENT_ID,
        ReconFields::PAYMENT_AMOUNT,
        ReconFields::BANK_REFERENCE_NUMBER
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
