<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return Constants::PAYMENT_COLUMN_HEADERS;
    }
}
