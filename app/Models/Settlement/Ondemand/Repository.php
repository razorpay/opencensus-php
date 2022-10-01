<?php

namespace RZP\Models\Settlement\Ondemand;

use Carbon\Carbon;
use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand';

    public function findByIdAndMerchantIdWithLock($settlementOndemandId, $merchantId)
    {
        assertTrue ($this->isTransactionActive());

        return Entity::lockForUpdate()
                    ->newQuery()
                    ->where(Entity::ID, $settlementOndemandId)
                    ->merchantId($merchantId)
                    ->first();
    }

    public function findByIdAndMerchantId($settlementOndemandId, $merchantId, string $connectionType = null)
    {
        $query = (empty($connectionType) === true) ?
            $this->newQuery() : $this->newQueryWithConnection($this->getConnectionFromType($connectionType));

        return $query
                    ->where(Entity::ID, $settlementOndemandId)
                    ->merchantId($merchantId)
                    ->first();
    }

    public function findByMerchantIdAndOndemandTriggerId($merchantId, $settlementOndemandTriggerId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::SETTLEMENT_ONDEMAND_TRIGGER_ID, $settlementOndemandTriggerId)
                    ->first();
    }

    public function findSettlementsCountTodayByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::CREATED_AT,'>=', Carbon::today()->getTimeStamp())
                    ->where(function ($query) {
                       $query->where(Entity::STATUS, '=', Status::INITIATED)
                             ->orWhere(Entity::STATUS, '=', Status::PARTIALLY_PROCESSED)
                             ->orWhere(Entity::STATUS, '=', Status::PROCESSED);
                    })
                     ->count();
    }

    public function findAmountSettledTodayByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->where(Entity::CREATED_AT,'>=', Carbon::today()->getTimeStamp())
                    ->where(function ($query) {
                        $query->where(Entity::STATUS, '=', Status::INITIATED)
                              ->orWhere(Entity::STATUS, '=', Status::PARTIALLY_PROCESSED)
                              ->orWhere(Entity::STATUS, '=', Status::PROCESSED);
                    })
                    ->sum(Entity::AMOUNT);
    }
}
