<?php

namespace RZP\Models\FundTransfer\Kotak\Reconciliation\V1;

use RZP\Models\Payout;
use RZP\Models\Settlement;
use RZP\Models\Transaction\ReconciledType;
use RZP\Models\FundTransfer\Attempt\Version;
use RZP\Models\FundTransfer\Kotak\Reconciliation\Base;

class RowProcessor extends Base\RowProcessor
{
    public function __construct($row)
    {
        parent::__construct($row);

        $this->version = Version::V1;
    }

    protected function fetchEntities()
    {
        if (strpos($this->reconEntityId, Settlement\Entity::getSign(), 0) === 0)
        {
            Settlement\Entity::verifyIdAndStripSign($this->reconEntityId);

            $this->reconEntity = $this->repo
                                      ->settlement
                                      ->findWithRelations(
                                            $this->reconEntityId,
                                            ['merchant', 'transaction', 'batchFundTransfer']);
        }
        else if(strpos($this->reconEntityId, Payout\Entity::getSign(), 0) === 0)
        {
            Payout\Entity::verifyIdAndStripSign($this->reconEntityId);

            $this->reconEntity = $this->repo
                                      ->payout
                                      ->findWithRelations(
                                            $this->reconEntityId,
                                            ['merchant', 'transaction', 'batchFundTransfer']);
        }
    }

    protected function updateEntities()
    {
        $this->updateUtrOnReconEntity();
        $this->reconEntity->setStatus($this->parsedData['status']);
        $this->reconEntity->setFailureReason($this->parsedData['failure_reason']);
        $this->reconEntity->setRemarks($this->parsedData['remarks']);

        $this->reconEntity->saveOrFail();

        $this->reconEntity->transaction->setReconciledAt($this->reconciledAt);
        $this->reconEntity->transaction->setReconciledType(ReconciledType::MIS);
        $this->reconEntity->transaction->saveOrFail();

        return $this->reconEntity;
    }

    protected function updateReconEntity()
    {
        return;
    }

    protected function updateSourceEntity()
    {
        return;
    }
}
