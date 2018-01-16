<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

abstract class RowProcessor extends BaseRowProcessor
{
    protected function parseRow()
    {
        $utr = trim($this->row[Headings::UTR_NUMBER]);

        if (empty($utr) === true)
        {
            $utr = null;
        }

        $this->parsedData = [
            'payment_ref_no'    => trim($this->row[Headings::PAYMENT_REF_NO] ?? null),
            'utr'               => $utr,
            'bank_status_code'  => trim($this->row[Headings::STATUS_OF_TRANSACTION] ?? null),
            'remarks'           => trim($this->row[Headings::REMARKS] ?? null),
            'payment_date'      => trim($this->row[Headings::PAYMENT_DATE] ?? null),
            'instrument_date'   => trim($this->row[Headings::INSTRUMENT_DATE] ?? null),
            'date_time'         => trim($this->row[Headings::DATE_TIME] ?? null),
            'cms_ref_no'        => trim($this->row[Headings::CMS_REF_NO] ?? null),
        ];

        $this->reconEntityId = $this->parsedData['payment_ref_no'];
    }
}
