<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use RZP\Models\FundTransfer\Axis\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class RowProcessor extends BaseRowProcessor
{
    const ATTEMPT_REFERENCE     = 'attempt_reference';
    const SETTLEMENT_REFERENCE  = 'settlement_reference';
    const UTR                   = 'utr';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const REMARKS               = 'remarks';
    const SETTLEMENT_DATE       = 'settlement_date';

    protected function parseRow()
    {
        $this->parsedData = [
            self::ATTEMPT_REFERENCE     => trim($this->row[Headings::ADDITIONAL_INFO3]),
            self::UTR                   => trim($this->row[Headings::RBI_SEQUENCE_NUMBER]),
            self::BANK_STATUS_CODE      => trim($this->row[Headings::STATUS]),
            self::REMARKS               => trim($this->row[Headings::RETURN_REASON]),
            self::SETTLEMENT_DATE       => trim($this->row[Headings::SETTLEMENT_DATE]),
            self::SETTLEMENT_REFERENCE  => trim($this->row[Headings::ADDITIONAL_INFO4])
        ];

        $this->reconEntityId = $this->parsedData[self::ATTEMPT_REFERENCE];
    }

    protected function updateReconEntity()
    {
        $this->reconEntity->setUtr($this->parsedData[self::UTR]);

        $this->reconEntity->setRemarks($this->parsedData[self::REMARKS]);

        $this->reconEntity->setBankStatusCode($this->parsedData[self::BANK_STATUS_CODE]);

        $this->reconEntity->setDateTime($this->parsedData[self::SETTLEMENT_DATE]);

        $this->reconEntity->saveOrFail();
    }
}
