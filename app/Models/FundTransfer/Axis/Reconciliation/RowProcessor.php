<?php

namespace RZP\Models\FundTransfer\Axis\Reconciliation;

use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Attempt;
use RZP\Models\FundTransfer\Axis\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class RowProcessor extends BaseRowProcessor
{
    const ATTEMPT_REFERENCE     = 'attempt_reference';
    const UTR                   = 'utr';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const REMARKS               = 'remarks';
    const SETTLEMENT_DATE       = 'settlement_date';
    const NAME_WITH_BENE_BANK   = 'name_with_bene_bank';

    protected function processRow()
    {
        $attemptRef = $this->getAttemptReference();

        $this->parsedData = [
            self::ATTEMPT_REFERENCE     => $attemptRef,
            self::UTR                   => $this->getNullOnEmpty(Headings::RBI_SEQUENCE_NUMBER),
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty(Headings::STATUS),
            self::REMARKS               => $this->getNullOnEmpty(Headings::RETURN_REASON),
            self::SETTLEMENT_DATE       => $this->getNullOnEmpty(Headings::SETTLEMENT_DATE),
            self::NAME_WITH_BENE_BANK   => null,
        ];

        $this->trace->info(TraceCode::FTA_RECON_PARSED_DATA, ['parsed_data' => $this->parsedData]);

        $this->reconEntityId = $this->parsedData[self::ATTEMPT_REFERENCE];
    }

    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $currentBankStatusCode = $this->reconEntity->getBankStatusCode();

        $newBankStatusCode = $this->parsedData[self::BANK_STATUS_CODE];

        $successStatus = Status::EXECUTED;

        $flipStatus = Status::getFlipStatus();

        if (($currentBankStatusCode === $successStatus) and
            (in_array($newBankStatusCode, $flipStatus, true) === true))
        {
            $this->reconEntity->setStatus(Attempt\Status::INITIATED);
        }

        $this->reconEntity->setRemarks($this->parsedData[self::REMARKS]);

        $this->reconEntity->setBankStatusCode($newBankStatusCode);

        $this->reconEntity->setDateTime($this->parsedData[self::SETTLEMENT_DATE]);

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        $utr = $this->reconEntity->getUtr();

        $utrFromFile = $this->parsedData[self::UTR];

        // Update UTR to the value from file only if it is not empty
        if (empty($utrFromFile) === false)
        {
            $utr = $utrFromFile;
        }

        return $utr;
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
