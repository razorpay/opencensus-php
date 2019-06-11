<?php

namespace RZP\Reconciliator\NetbankingCub;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\NetbankingCub\ReconFields;

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
}