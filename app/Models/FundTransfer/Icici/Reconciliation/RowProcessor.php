<?php

namespace RZP\Models\FundTransfer\Icici\Reconciliation;

use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;
use RZP\Models\FundTransfer\Icici\Headings;

class RowProcessor extends RowProcessor
{
    const PAYMENT_REF_NO    = 'payment_ref_no';
    const UTR               = 'utr';
    const BANK_STATUS_CODE  = 'bank_status_code';
    const REMARKS           = 'remarks';
    const PAYMENT_DATE      = 'payment_date';
    const CMS_REF_NO        = 'cms_ref_no';

    // Modes
    const RTGS = AUTORTGS;
    const NEFT = AUTONEFT;
    #TODO:: Find about IFT, IMPS

    // Bank Status Codes
    const PAID      = 'Paid';
    const CANCELLED = 'Cancelled';
    const AWAITING  = 'Awaiting Liquidation';

    protected function parseRow()
    {
        $bankStatus = $this->parsedData[self::BANK_STATUS_CODE];

        $mode = trim($this->parsedData[Headings::PAYMENT_MODE]) ?? null;

        $remarks = null;
        $utr = null;

        switch ($mode)
        {
            case self::RTGS:
                if ($bankStatus === self::PAID)
                {
                    $utr = trim($this->row[Headings::REMARKS] ?? null);
                    $remarks = null;
                }
                else if ($bankStatus === self::CANCELLED)
                {
                    $remarks = trim($this->row[Headings::REMARKS] ?? null);
                }

                break;

            case self::NEFT:
                $remarks = trim($this->row[Headings::REMARKS] ?? null);

                $utr = trim($this->row[Headings::CMS_REF_NO]) ?: null;

                break;
        }

        $this->parsedData = [
            self::PAYMENT_REF_NO    => trim($this->row[Headings::PAYMENT_REF_NO] ?? null),
            self::UTR               => $utr,
            self::BANK_STATUS_CODE  => $bankStatus,
            self::REMARKS           => $remarks,
            self::PAYMENT_DATE      => trim($this->row[Headings::PAYMENT_DATE] ?? null),
            self::CMS_REF_NO        => trim($this->row[Headings::CMS_REF_NO] ?? null),
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