<?php

namespace RZP\Reconciliator\NetbankingCbi;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\NetbankingCbi\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        ReconFields::PAYMENT_ID,
        ReconFields::BANK_REFERENCE_NUMBER,
        ReconFields::AMOUNT,
        ReconFields::STATUS,
        ReconFields::DATE,
        ReconFields::ACCOUNT_NUMBER,
        ReconFields::ACCOUNT_TYPE,
    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
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
