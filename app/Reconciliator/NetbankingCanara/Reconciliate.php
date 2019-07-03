<?php

namespace RZP\Reconciliator\NetbankingCanara;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;


class Reconciliate extends Base\Reconciliate
{
    public function getColumnHeadersForType($type)
    {
        return Constants::PAYMENT_COLUMN_HEADERS;
    }

    public function getDelimiter()
    {
        return '|';
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }
}
