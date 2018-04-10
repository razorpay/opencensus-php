<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use RZP\Models\FundTransfer\Axis\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class RowProcessor extends BaseRowProcessor
{
    const ATTEMPT_REFERENCE     = 'attempt_reference';
    const UTR                   = 'utr';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const REMARKS               = 'remarks';
    const SETTLEMENT_DATE       = 'settlement_date';

    protected function parseRow()
    {
        $attemptRef = $this->getAttemptReference();

        $this->parsedData = [
            self::ATTEMPT_REFERENCE     => $attemptRef,
            self::UTR                   => $this->getNullOnEmpty(Headings::RBI_SEQUENCE_NUMBER),
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty(Headings::STATUS),
            self::REMARKS               => $this->getNullOnEmpty(Headings::RETURN_REASON),
            self::SETTLEMENT_DATE       => $this->getNullOnEmpty(Headings::SETTLEMENT_DATE),
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

    protected function getAttemptReference()
    {
        $additionalInfo3 = $this->getNullOnEmpty(Headings::ADDITIONAL_INFO3);

        //
        // For SDMC format files, Axis sends back data in these columns,
        // and attempt in additional info 3
        //
        if ($additionalInfo3 !== null)
        {
            return $additionalInfo3;
        }

        //
        // For MDMC, they send these values as empty,
        // and attempt id in first column.
        //
        $reference = $this->getNullOnEmpty(Headings::FILE_LEVEL_REFERENCE);

        return $reference;
    }
}
