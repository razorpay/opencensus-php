<?php

namespace RZP\Reconciliator\NetbankingYesb;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Mozart\NetbankingYesb\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        ReconFields::MERCHANT_CODE,
        ReconFields::CLIENT_CODE,
        ReconFields::PAYMENT_ID,
        ReconFields::TRANSACTION_DATE,
        ReconFields::AMOUNT,
        ReconFields::SERVICE_CHARGES,
        ReconFields::BANK_REFERENCE_ID,
        ReconFields::TRANSACTION_STATUS,
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
            FileProcessor::LINES_FROM_TOP    => 6,
            FileProcessor::LINES_FROM_BOTTOM => 0,
        ];
    }
}
