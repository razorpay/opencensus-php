<?php

namespace RZP\Reconciliator\NetbankingScb;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\NetbankingScb\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    public function getColumnHeadersForType($type)
    {
        return ReconFields::RECON_FIELDS;
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
