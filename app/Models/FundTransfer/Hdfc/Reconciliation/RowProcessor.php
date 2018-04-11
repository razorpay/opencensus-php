<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use RZP\Models\FundTransfer\Hdfc\Constants;
use RZP\Models\FundTransfer\Hdfc\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class RowProcessor extends BaseRowProcessor
{
    const PAYMENT_REF_NO    = 'payment_ref_no';
    const UTR               = 'utr';
    const BANK_STATUS_CODE  = 'bank_status_code';
    const PAYMENT_DATE      = 'payment_date';
    const CMS_REF_NO        = 'cms_ref_no';
    const FAILURE_REASON    = 'failure_reason';
    const REMARK            = 'remark';

    protected function parseRow()
    {
        $this->parsedData = [
            self::PAYMENT_REF_NO    => trim($this->row[Headings::CUSTOMER_REFERENCE_NUMBER] ?? null),
            self::UTR               => $this->getUtr(),
            self::BANK_STATUS_CODE  => trim($this->row[Headings::TRANSACTION_STATUS] ?? null),
            self::PAYMENT_DATE      => trim($this->row[Headings::TRANSACTION_DATE] ?? null),
            self::REMARK            => trim($this->row[Headings::REJECT_REASON] ?? null),
            self::CMS_REF_NO        => trim($this->row[Headings::BANK_REFERENCE_NO] ?? null)
        ];

        $this->reconEntityId = $this->parsedData[self::PAYMENT_REF_NO];
    }

    protected function getUtr(): string
    {
        $key = ($this->row[Headings::TRANSACTION_TYPE] === Constants::RTGS) ?
                Headings::UTR : Headings::BANK_REFERENCE_NO;

        $referenceNumber = trim($this->row[$key]);

        return (empty($referenceNumber) === true) ? null : $referenceNumber;
    }

    /**
     * Updates the attempt data with the recon response
     */
    protected function updateReconEntity()
    {
        $this->reconEntity->setUtr($this->parsedData[self::UTR]);

        $this->reconEntity->setCmsRefNo($this->parsedData[self::CMS_REF_NO]);

        $this->reconEntity->setBankStatusCode($this->parsedData[self::BANK_STATUS_CODE]);

        $this->reconEntity->setRemarks($this->parsedData[self::REMARK]);

        $this->reconEntity->saveOrFail();
    }
}
