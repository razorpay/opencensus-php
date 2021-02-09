<?php

namespace RZP\Reconciliator\NetbankingAusf;

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

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    public function getDelimiter()
    {
        return ',';
    }

}
