<?php

namespace RZP\Reconciliator\Phonepe;

use RZP\Reconciliator\Base;
use RZP\Reconciliator\FileProcessor;
use RZP\Gateway\Mozart\WalletPhonepe\ReconFields;

class Reconciliate extends Base\Reconciliate
{
    protected $columnHeaders = [
        ReconFields::PAYMENT_TYPE,
        ReconFields::RZP_ID,
        ReconFields::ORDER_ID,
        ReconFields::PHONEPE_ID,
        ReconFields::FROM,
        ReconFields::CREATION_DATE,
        ReconFields::TRANSACTION_DATE,
        ReconFields::SETTLEMENT_DATE,
        ReconFields::BANK_REFERENCE_NO,
        ReconFields::AMOUNT,
        ReconFields::FEE,
        ReconFields::IGST,
        ReconFields::CGST,
        ReconFields::SGST,
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
