<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\RowProcessor;

use RZP\Models\FundTransfer\Attempt;

class V3 extends Base
{
    public function __construct($row)
    {
        parent::__construct($row);

        $this->version = Attempt\Version::V3;
    }

    protected function fetchEntities()
    {
        $this->reconEntity = $this->repo
                                  ->fund_transfer_attempt
                                  ->findWithRelations(
                                        $this->reconEntityId,
                                        ['source', 'source.transaction', 'source.merchant' , 'batchFundTransfer']);
    }

    protected function updateEntities()
    {
        parent::updateEntities();

        $this->reconEntity->setUtr($this->parsedData['utr']);
        $this->reconEntity->setStatus($this->parsedData['status']);
        $this->reconEntity->setFailureReason($this->parsedData['failure_reason']);
        $this->reconEntity->setRemarks($this->parsedData['remarks']);
        $this->reconEntity->setBankStatusCode($this->parsedData['bank_status_code']);
        $this->reconEntity->setDateTime($this->parsedData['date_time']);
        $this->reconEntity->setCmsRefNo($this->parsedData['cms_ref_no']);
        $this->reconEntity->saveOrFail();

        $source = $this->reconEntity->source;
        $source->setUtr($this->parsedData['utr']);
        $source->setFailureReason($this->parsedData['failure_reason']);
        $source->setStatus($this->parsedData['status']);
        $source->setRemarks($this->parsedData['remarks']);

        $source->saveOrFail();

        $source->transaction->setReconciledAt($this->reconciledAt);
        $source->transaction->saveOrFail();

        return $source;
    }
}