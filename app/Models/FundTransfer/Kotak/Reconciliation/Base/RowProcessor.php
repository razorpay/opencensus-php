<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

use RZP\Models\FundTransfer\Kotak\Headings;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

abstract class RowProcessor extends BaseRowProcessor
{
    protected function processRow()
    {
        $this->parsedData = [
            'payment_ref_no'    => $this->getNullOnEmpty(Headings::PAYMENT_REF_NO),
            'utr'               => $this->getNullOnEmpty(Headings::UTR_NUMBER),
            'bank_status_code'  => $this->getNullOnEmpty(Headings::STATUS_OF_TRANSACTION),
            'remarks'           => $this->getNullOnEmpty(Headings::REMARKS),
            'payment_date'      => $this->getNullOnEmpty(Headings::PAYMENT_DATE),
            'instrument_date'   => $this->getNullOnEmpty(Headings::INSTRUMENT_DATE),
            'date_time'         => $this->getNullOnEmpty(Headings::DATE_TIME),
            'cms_ref_no'        => $this->getNullOnEmpty(Headings::CMS_REF_NO),
        ];

        $this->reconEntityId = $this->parsedData['payment_ref_no'];
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData['utr'];
    }
}
