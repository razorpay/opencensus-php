<?php

namespace RZP\Reconciliator\NetbankingKvb;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Mozart\NetbankingKvb\ReconFields;

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

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 3,
            FileProcessor::LINES_FROM_BOTTOM => 0,
        ];
    }

    public function getDelimiter()
    {
        return '|';
    }
}
