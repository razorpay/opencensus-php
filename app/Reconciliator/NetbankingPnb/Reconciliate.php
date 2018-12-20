<?php

namespace RZP\Reconciliator\NetbankingPnb;

use RZP\Reconciliator\Base;
use RZP\Gateway\Netbanking\Pnb\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconFields::getPaymentColumnHeaders();
    }

    public function getDelimiter()
    {
        return '^';
    }
}
