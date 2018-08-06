<?php

namespace RZP\Models\FundTransfer\Yesbank\Reconciliation;

use RZP\Models\FundTransfer\Yesbank\Request\Status as StatusRequest;
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
    const MODE              = 'mode';

    /**
     * This will update the status based on the transfer API response
     *
     * @return null
     */
    public function updateTransferStatus()
    {
        $this->setParsedData($this->row);

        $this->fetchEntities();

        $this->updateEntities();

        return $this->reconEntity;
    }

    /**
     * for API based status check parse row will make status request and formats the response as required
     *
     * {@inheritdoc}
     */
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
            self::UTR              => $response[self::UTR],
            self::BANK_STATUS_CODE => $response[self::BANK_STATUS_CODE],
            self::REMARK           => $response[self::REMARK],
            self::PAYMENT_DATE     => $response[self::PAYMENT_DATE] ?? null,
            self::REFERENCE_NUMBER => $response[self::REFERENCE_NUMBER],
            self::MODE             => $response[self::MODE],
        ];

        $this->reconEntityId = $response[self::PAYMENT_REF_NO];
    }

    /**
     * {@inheritdoc}
     */
    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $this->reconEntity->setBankStatusCode($this->parsedData[self::BANK_STATUS_CODE]);

        $this->reconEntity->setDateTime($this->parsedData[self::PAYMENT_DATE]);

        $this->reconEntity->setRemarks($this->parsedData[self::REMARK]);

        $this->reconEntity->setMode($this->parsedData[self::MODE]);

        //
        // Reference number is only available in transfer request's response.
        // It is null in status request's response.
        //
        if (empty($this->parsedData[self::REFERENCE_NUMBER]) === false)
        {
            $this->reconEntity->setCmsRefNo($this->parsedData[self::REFERENCE_NUMBER]);
        }

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData[self::UTR];
    }
}
