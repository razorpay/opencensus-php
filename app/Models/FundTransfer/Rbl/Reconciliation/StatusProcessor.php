<?php

namespace RZP\Models\FundTransfer\Rbl\Reconciliation;

use RZP\Trace\TraceCode;
use RZP\Models\FundTransfer\Rbl\Request\Status as StatusRequest;
use RZP\Models\FundTransfer\Base\Reconciliation\Constants as ReconConstants;
use RZP\Models\FundTransfer\Base\Reconciliation\RowProcessor as BaseRowProcessor;

class StatusProcessor extends BaseRowProcessor
{
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

        if (empty($response) === false)
        {
            $this->setParsedData($response);
        }
    }

    protected function setParsedData(array $response)
    {
        $this->parsedData = [
            ReconConstants::REFERENCE_NUMBER      => $response[ReconConstants::REFERENCE_NUMBER],
            ReconConstants::UTR                   => $response[ReconConstants::UTR],
            ReconConstants::BANK_STATUS_CODE      => $response[ReconConstants::BANK_STATUS_CODE],
            ReconConstants::REMARKS               => $response[ReconConstants::REMARKS],
            ReconConstants::PAYMENT_DATE          => $response[ReconConstants::PAYMENT_DATE],
            // Won't be present in case of success response
            ReconConstants::PUBLIC_FAILURE_REASON => $response[ReconConstants::PUBLIC_FAILURE_REASON] ?? null,
            ReconConstants::NAME_WITH_BENE_BANK   => null,
        ];

        $this->reconEntityId = $response[ReconConstants::PAYMENT_REF_NO];

        $this->trace->info(TraceCode::FTA_RECON_PARSED_DATA, ['parsed_data' => $this->parsedData]);
    }

    protected function updateReconEntity()
    {
        $this->updateUtrOnReconEntity();

        $this->reconEntity->setBankStatusCode($this->parsedData[ReconConstants::BANK_STATUS_CODE]);

        $this->reconEntity->setDateTime($this->parsedData[ReconConstants::PAYMENT_DATE]);

        $this->reconEntity->setCmsRefNo($this->parsedData[ReconConstants::REFERENCE_NUMBER]);

        $this->reconEntity->setRemarks($this->parsedData[ReconConstants::REMARKS]);

        $this->reconEntity->saveOrFail();
    }

    protected function getUtrToUpdate()
    {
        return $this->parsedData[ReconConstants::UTR];
    }
}
