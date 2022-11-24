<?php

namespace RZP\Reconciliator\NetbankingBobV2;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Mozart\NetbankingBob\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        ReconFields::ACCOUNT_NUMBER,
        ReconFields::PAYMENT_ID,
        ReconFields::BANK_REFERENCE_NUMBER,
        ReconFields::STATUS,
        ReconFields::AMOUNT ,

    ];

    public function getColumnHeadersForType($type)
    {
        return $this->columnHeaders;
    }

    protected function getTypeName($fileName)
    {
        return self::PAYMENT;
    }

    public function getNumLinesToSkip(array $fileDetails)
    {
        return [
            FileProcessor::LINES_FROM_TOP    => 1,
            FileProcessor::LINES_FROM_BOTTOM => 0,
        ];
    }
}
