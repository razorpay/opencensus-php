<?php

namespace RZP\Models\Settlement\Ondemand\FeatureConfig;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'settlement.ondemand.feature_config';

    public function getConfigByMerchantId($merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->firstOrFail();
    }
}
