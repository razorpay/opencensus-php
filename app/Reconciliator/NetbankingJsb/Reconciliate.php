<?php

namespace RZP\Reconciliator\NetbankingJsb;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Mozart\NetbankingJsb\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    public function getColumnHeadersForType($type)
    {
        return ReconFields::RECON_FIELDS;
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDelimiter()
    {
        return '|';
    }
}
