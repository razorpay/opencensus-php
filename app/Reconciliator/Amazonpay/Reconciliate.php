<?php

namespace RZP\Reconciliator\Amazonpay;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;

class Reconciliate extends Base\Reconciliate
{
    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0
        ];
    }

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getColumnHeadersForType($type)
    {
        return ReconHeaders::COLUMN_HEADERS;
    }
}
