<?php


namespace RZP\Models\CreditTransfer;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'credit_transfer';

    public function findCreditTransferBySourceId($sourceId)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $sourceId)
                    ->first();
    }
}
