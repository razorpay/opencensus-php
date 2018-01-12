<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Models\FundTransfer\Icici\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class RowProcessor extends BaseRowProcessor
{
    const PAYMENT_REF_NO    = 'payment_ref_no';
    const UTR               = 'utr';
    const BANK_STATUS_CODE  = 'bank_status_code';
    const REMARKS           = 'remarks';
    const PAYMENT_DATE      = 'payment_date';
    const CMS_REF_NO        = 'cms_ref_no';

    protected function parseRow()
    {
        $bankStatus = $this->row[Headings::STATUS];

        $mode = trim($this->row[Headings::PAYMENT_MODE]) ?? null;

        $remarks = trim($this->row[Headings::REMARKS] ?? null);

        $cmsRefNo = trim($this->row[Headings::CMS_REF_NO] ?? null);

        $utr = null;

        switch ($mode)
        {
            case Mode::RTGS:
                $utr = (($bankStatus === Status::PAID) ? $remarks : $cmsRefNo);
                break;

            case Mode::NEFT:
            case Mode::IFT:
                $utr = $cmsRefNo;
                break;
        }

        $this->parsedData = [
            self::PAYMENT_REF_NO    => trim($this->row[Headings::PAYMENT_REF_NO] ?? null),
            self::UTR               => $utr,
            self::BANK_STATUS_CODE  => $bankStatus,
            self::REMARKS           => $remarks,
            self::PAYMENT_DATE      => trim($this->row[Headings::PAYMENT_DATE] ?? null),
            self::CMS_REF_NO        => $cmsRefNo,
        ];

        $this->reconEntityId = $this->parsedData['payment_ref_no'];
    }

    protected function fetchEntities()
    {
        $this->reconEntity = $this->repo->fund_transfer_attempt->findByIdCaseInsensitive($this->reconEntityId);

        if (empty($this->reconEntity) === true)
        {
            // This will be traced as error in Base/RowProcessor
            return;
        }
    }

    protected function updateReconEntity()
    {
        $this->reconEntity->setUtr($this->parsedData[self::UTR]);
        $this->reconEntity->setRemarks($this->parsedData[self::REMARKS]);
        $this->reconEntity->setBankStatusCode($this->parsedData[self::BANK_STATUS_CODE]);
        $this->reconEntity->setCmsRefNo($this->parsedData[self::CMS_REF_NO]);

        $this->reconEntity->saveOrFail();
    }
}