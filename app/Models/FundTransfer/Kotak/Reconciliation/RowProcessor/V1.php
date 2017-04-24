<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\RowProcessor;

use RZP\Models\FundTransfer\Attempt\Version;

class V1 extends Base
{
    public function __construct($row)
    {
        parent::__construct($row);

        $this->version = Version::V1;
    }

    protected function fetchEntities()
    {
        if (strpos($entityId, Settlement\Entity::getSign(), 0) === 0)
        {
            Settlement\Entity::verifyIdAndStripSign($entityId);

            $this->reconEntity = $this->repo
                                      ->settlement
                                      ->findWithRelations(
                                            $this->reconEntityId,
                                            ['merchant', 'transaction', 'batchFundTransfer']);
        }
        else if(strpos($entityId, Payout\Entity::getSign(), 0) === 0)
        {
            Payout\Entity::verifyIdAndStripSign($entityId);

            $this->reconEntity = $this->repo
                                      ->payout
                                      ->findWithRelations(
                                            $this->reconEntityId,
                                            ['merchant', 'transaction', 'batchFundTransfer']);
        }
    }

    protected function updateEntities()
    {
        parent::updateEntities();

        $this->reconEntity->setUtr($this->parsedData['utr']);
        $this->reconEntity->setStatus($this->parsedData['status']);
        $this->reconEntity->setFailureReason($this->parsedData['failure_reason']);
        $this->reconEntity->setRemarks($this->parsedData['remarks']);

        $this->reconEntity->saveOrFail();

        $this->reconEntity->transaction->setReconciledAt($this->reconciledAt);
        $this->reconEntity->transaction->saveOrFail();

        return $this->reconEntity;
    }
}