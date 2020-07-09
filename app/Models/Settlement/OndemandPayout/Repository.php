<?php

namespace RZP\Models\Settlement\OndemandPayout;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand_payout';

    public function findbyIdAndPayoutIdWithLock($id, $payoutId): Entity
    {
        assertTrue ($this->isTransactionActive());

        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::ID, $id)
                    ->where(Entity::PAYOUT_ID, $payoutId)
                    ->firstOrFail();
    }
    public function findByIdAndMerchantIdWithLock($id, $merchantId): Entity
    {
        assertTrue ($this->isTransactionActive());

        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::ID, $id)
                    ->merchantId($merchantId)
                    ->first();
    }

    public function fetchIdsByOndemandIdAndMerchantId($settlementOndemandId, $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::SETTLEMENT_ONDEMAND_ID, $settlementOndemandId)
                    ->merchantId($merchantId)
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function fetchByOndemandIdAndMerchantId($settlementOndemandId, $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::SETTLEMENT_ONDEMAND_ID, $settlementOndemandId)
                    ->merchantId($merchantId)
                    ->get();
    }
}
