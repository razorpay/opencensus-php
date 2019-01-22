<?php

namespace RZP\Models\FundTransfer\Hdfc\Reconciliation;

use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Hdfc\Constants;
use RZP\Models\FundTransfer\Hdfc\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class SuccessRowProcessor extends BaseRowProcessor
{
    const PAYMENT_REF_NO        = 'payment_ref_no';
    const UTR                   = 'utr';
    const BANK_STATUS_CODE      = 'bank_status_code';
    const PAYMENT_DATE          = 'payment_date';
    const CMS_REF_NO            = 'cms_ref_no';
    const FAILURE_REASON        = 'failure_reason';
    const REMARKS               = 'remarks';
    const NAME_WITH_BENE_BANK   = 'name_with_bene_bank';

    protected function processRow()
    {
        $this->parsedData = [
            self::PAYMENT_REF_NO        => $this->getNullOnEmpty(Headings::CUSTOMER_REFERENCE_NUMBER),
            self::UTR                   => $this->getUtr(),
            self::BANK_STATUS_CODE      => $this->getNullOnEmpty(Headings::TRANSACTION_STATUS),
            self::PAYMENT_DATE          => $this->getNullOnEmpty(Headings::TRANSACTION_DATE),
            self::REMARKS               => $this->getNullOnEmpty(Headings::REJECT_REASON),
            self::CMS_REF_NO            => $this->getNullOnEmpty(Headings::BANK_REFERENCE_NO),
            self::NAME_WITH_BENE_BANK   => null,
        ];

        $this->reconEntityId = $this->parsedData[self::PAYMENT_REF_NO];

        $this->trace->info(TraceCode::FTA_RECON_PARSED_DATA, ['parsed_data' => $this->parsedData]);
    }

    /**
     * Gives UTR of the transaction.
     * For the transaction of type  RTGS, UTR will be available in UTR field
     * and for all other types it will be available in BANK_REFERENCE_NO field
     *
     * @return string
     */
    protected function getUtr()
    {
        $transactionType = $this->getNullOnEmpty(Headings::TRANSACTION_TYPE);

        $key = ($transactionType === Constants::RTGS) ?
                Headings::UTR : Headings::BANK_REFERENCE_NO;

        return $this->getNullOnEmpty($key);
    }

    /**
     * Updates the attempt data with the recon response
     */
    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $this->reconEntity->setCmsRefNo($this->parsedData[self::CMS_REF_NO]);

        $this->reconEntity->setBankStatusCode($this->parsedData[self::BANK_STATUS_CODE]);

        $this->reconEntity->setRemarks($this->parsedData[self::REMARKS]);

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData[self::UTR];
    }
}
