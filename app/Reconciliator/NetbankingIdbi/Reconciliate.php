<?php

namespace RZP\Reconciliator\NetbankingIdbi;

use RZP\Reconciliator\Base;
use RZP\Gateway\Mozart\NetbankingIdbi\ReconFields;
use RZP\Reconciliator\FileProcessor;


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
        return ',';
    }
    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }
}
