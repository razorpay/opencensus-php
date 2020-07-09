<?php

namespace RZP\Models\Settlement\OndemandPayout;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand_payout';

    public function findbyIdAndPayoutId($id, $payoutId): Entity
    {
        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::ID, $id)
                    ->where(Entity::PAYOUT_ID, $payoutId)
                    ->firstOrFail();
    }
    public function findByIdAndMerchantId($id, $merchantId): Entity
    {
        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::ID, $id)
                    ->merchantId($merchantId)
                    ->first();
    }

    public function fetchIdsByOndemandIdAndMerchant($settlementOndemandId, $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::SETTLEMENT_ONDEMAND_ID, $settlementOndemandId)
                    ->merchantId($merchantId)
                    ->pluck(Entity::ID)
                    ->toArray();
    }

    public function fetchByOndemandIdAndMerchant($settlementOndemandId, $merchantId)
    {
        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::SETTLEMENT_ONDEMAND_ID, $settlementOndemandId)
                    ->merchantId($merchantId)
                    ->get();
    }
}
