<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Attempt;
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
        $bankStatus = $this->getNullOnEmpty(Headings::STATUS);

        $mode       = $this->getNullOnEmpty(Headings::PAYMENT_MODE);

        $remarks    = $this->getNullOnEmpty(Headings::REMARKS);

        $cmsRefNo   = $this->getNullOnEmpty(Headings::CMS_REF_NO);

        $utr = null;

        switch ($mode)
        {
            case Mode::RTGS:
                $utr = (($bankStatus === Status::PAID) ? $remarks : null);
                break;

            case Mode::NEFT:
            case Mode::IFT:
                $utr = $cmsRefNo;
                break;
        }

        $this->parsedData = [
            self::PAYMENT_REF_NO    => $this->getNullOnEmpty(Headings::PAYMENT_REF_NO),
            self::UTR               => $utr,
            self::BANK_STATUS_CODE  => $bankStatus,
            self::REMARKS           => $remarks,
            self::PAYMENT_DATE      => $this->getNullOnEmpty(Headings::PAYMENT_DATE),
            self::CMS_REF_NO        => $cmsRefNo,
        ];

        $this->reconEntityId = $this->parsedData[self::PAYMENT_REF_NO];
    }

    protected function updateReconEntity()
    {
        $currentBankStatusCode = $this->reconEntity->getBankStatusCode();

        $newBankStatusCode = $this->parsedData[self::BANK_STATUS_CODE];

        $currentStatus = $this->reconEntity->getStatus();

        if ((in_array($currentBankStatusCode, Status::SUCCESS_STATUS, true) === true) and
            ($newBankStatusCode === Status::CANCELLED))
        {
            $this->reconEntity->setStatus(Attempt\Status::INITIATED);
        }
        else if (($currentBankStatusCode === Status::CANCELLED) and
            ($currentBankStatusCode !== $newBankStatusCode))
        {
            $this->trace->error(TraceCode::FTA_FILE_RECON_INVALID_STATUS_CHANGE,
                [
                    'parsed_data'               => $this->parsedData,
                    'row'                       => $this->row,
                    'current_bank_status_code'  => $currentBankStatusCode,
                    'current_status'            => $currentStatus,
                ]);
        }

        $this->reconEntity->setUtr($this->parsedData[self::UTR]);
        $this->reconEntity->setRemarks($this->parsedData[self::REMARKS]);
        $this->reconEntity->setBankStatusCode($newBankStatusCode);
        $this->reconEntity->setCmsRefNo($this->parsedData[self::CMS_REF_NO]);

        $this->reconEntity->saveOrFail();
    }
}
