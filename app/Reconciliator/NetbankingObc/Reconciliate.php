<?php

namespace RZP\Reconciliator\NetbankingObc;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    private $columnHeaders = [
        'Bank Name',
        'Transaction Date',
        'Payee ID',
        'Transaction Amount',
        'PGI/Merchant Transaction Ref#',
        'Bank Transaction Ref#'
    ];

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    public function getDelimiter()
    {
        return '|';
    }
}
