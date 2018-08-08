<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use RZP\Models\FundTransfer\Rbl\Request\Status as StatusRequest;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class StatusProcessor extends BaseRowProcessor
{
    const UTR               = 'utr';
    const BANK_STATUS_CODE  = 'bank_status_code';
    const PAYMENT_DATE      = 'payment_date';
    const REMARK            = 'remark';
    const PAYMENT_REF_NO    = 'payment_ref_no';
    const RRN               = 'rrn';
    const REFERENCE_NUMBER  = 'reference_number';

    public function updateTransferStatus()
    {
        $this->setParsedData($this->row);

        $this->fetchEntities();

        $this->updateEntities();

        return $this->reconEntity;
    }

    protected function processRow()
    {
        $response = (new StatusRequest())->init()
                                         ->setEntity($this->row)
                                         ->makeRequest();

        $this->setParsedData($response);
    }

    protected function setParsedData(array $response)
    {
        $this->parsedData = [
            self::REFERENCE_NUMBER => $response[self::REFERENCE_NUMBER],
            self::UTR              => $response[self::UTR],
            self::BANK_STATUS_CODE => $response[self::BANK_STATUS_CODE],
            self::REMARK           => $response[self::REMARK],
            self::PAYMENT_DATE     => $response[self::PAYMENT_DATE]
        ];

        $this->reconEntityId = $response[self::PAYMENT_REF_NO];
    }

    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $this->reconEntity->setBankStatusCode($this->parsedData[self::BANK_STATUS_CODE]);

        $this->reconEntity->setDateTime($this->parsedData[self::PAYMENT_DATE]);

        $this->reconEntity->setCmsRefNo($this->parsedData[self::REFERENCE_NUMBER]);

        $this->reconEntity->setRemarks($this->parsedData[self::REMARK]);

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData[self::UTR];
    }
}
