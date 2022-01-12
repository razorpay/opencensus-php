<?php

namespace RZP\Models\Settlement\EarlySettlementFeaturePeriod;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'early_settlement_feature_period';

    public function findByDisableDateInChunks($date, $skip, $limit)
    {
        return $this->newQuery()
                    ->where(Entity::DISABLE_DATE,'<', $date)
                    ->skip($skip)
                    ->take($limit)
                    ->pluck(Entity::MERCHANT_ID)
                    ->toArray();
    }

    public function findFeaturePeriodByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->firstOrFail();

    }

    public function deleteFeaturePeriod($featurePeriod)
    {
        $this->deleteOrFail($featurePeriod);
    }
}
