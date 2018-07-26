<?php

namespace RZP\Reconciliator\NetbankingAllahabad;

use RZP\Reconciliator\Base;

class Reconciliate extends Base\Reconciliate
{
    public function getColumnHeadersForType($type)
    {
        return Constants::PAYMENT_COLUMN_HEADERS;
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
