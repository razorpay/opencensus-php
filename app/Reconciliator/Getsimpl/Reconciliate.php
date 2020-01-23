<?php

namespace RZP\Reconciliator\Getsimpl;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Mozart\Getsimpl\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        ReconFields::AMOUNT ,
        ReconFields::PAY_ID,
        ReconFields::PHONE,
        ReconFields::TYPE,
        ReconFields::TRANSACTION_ID,
    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    protected function getTypeName($fileName)
    {
        return self::COMBINED;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0,
        ];
    }
}