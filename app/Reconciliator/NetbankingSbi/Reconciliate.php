<?php

namespace RZP\Reconciliator\NetbankingSbi;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        'Merchant ID',
        'Gateway Reference Number',
        'Bank Transaction ReferenceNo',
        'Transaction Amount',
        'STATUS',
        'TRANSACTION Date',
    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    public function getDelimiter()
    {
        return ',';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }
}
