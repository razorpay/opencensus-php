<?php

namespace RZP\Reconciliator\NetbankingBob;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getDelimiter()
    {
        return '|';
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 2,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }
}
