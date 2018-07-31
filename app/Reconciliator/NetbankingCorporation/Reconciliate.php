<?php

namespace RZP\Reconciliator\NetbankingCorporation;

use RZP\Reconciliator\Base;
use RZP\Gateway\Netbanking\Corporation\ReconcilationFields;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconcilationFields::getPaymentColumnHeaders();
    }

    public function getDelimiter()
    {
        return '|';
    }
}
