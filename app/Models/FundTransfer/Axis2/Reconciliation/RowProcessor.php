<?php

namespace RZP\Models\FundTransfer\Axis2\Reconciliation;

use RZP\Models\FundTransfer\Axis2\Headings;
use RZP\Models\FundTransfer\Attempt\Status as AttemptStatus;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class RowProcessor extends BaseRowProcessor
{
    const ATTEMPT_REFERENCE     = 'attempt_reference';
    const UTR                   = 'utr';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const REMARKS               = 'remarks';
    const SETTLEMENT_DATE       = 'settlement_date';
    const CMS_REFERENCE_NO      = 'cms_reference_no';
    const NAME_WITH_BENE_BANK   = 'name_with_bene_bank';

    protected function processRow()
    {
        $utr = $this->getUtr();

        $this->parsedData = [
            self::ATTEMPT_REFERENCE     => $this->getNullOnEmpty(Headings::CUSTOMER_UNIQUE_NO),
            self::UTR                   => $utr,
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty(Headings::STATUS_CODE),
            self::REMARKS               => $this->getNullOnEmpty(Headings::STATUS_DESCRIPTION),
            self::SETTLEMENT_DATE       => $this->getNullOnEmpty(Headings::PAYMENT_RUN_DATE),
            self::CMS_REFERENCE_NO      => $this->getNullOnEmpty(Headings::BANK_REFERENCE_NUMBER),
            self::NAME_WITH_BENE_BANK   => null,
        ];

        $this->reconEntityId = $this->parsedData[self::ATTEMPT_REFERENCE];
    }

    protected function getUtr()
    {
        $utr = $this->getNullOnEmpty(Headings::TRANSACTION_UTR_NUMBER);

        $refNo = $this->getNullOnEmpty(Headings::BANK_REFERENCE_NUMBER);

        return $utr ?? $refNo;
    }

    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $currentBankStatusCode = $this->reconEntity->getBankStatusCode();

        $newBankStatusCode = $this->parsedData[self::BANK_STATUS_CODE];
        
        $this->reconEntity->setRemarks($this->parsedData[self::REMARKS]);

        $this->reconEntity->setBankStatusCode($newBankStatusCode);

        $this->reconEntity->setDateTime($this->parsedData[self::SETTLEMENT_DATE]);

        $this->reconEntity->setCmsRefNo($this->parsedData[self::CMS_REFERENCE_NO]);

        $flipStatus = Status::getFlipStatus();

        $successStatuses = Status::getSuccessfulStatus();

        if ((Status::inStatus($successStatuses, $currentBankStatusCode) === true) and
            (in_array($newBankStatusCode, $flipStatus, true) === true))
        {
            $this->reconEntity->setStatus(AttemptStatus::INITIATED);
        }

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData[self::UTR];
    }
}
