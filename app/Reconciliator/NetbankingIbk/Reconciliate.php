<?php

namespace RZP\Reconciliator\NetbankingIbk;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\NetbankingIbk\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconFields::ReconFields;
    }

    public function getDelimiter()
    {
        return '|';
    }
}
