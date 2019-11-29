<?php

namespace RZP\Models\D2cBureauReport;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'd2c_bureau_report';

    public function findByProviderDetailIdAndMerchantId(string $provider, string $detailId, string $merchantId)
    {
        return $this->newQuery()
                    ->where(Entity::D2C_BUREAU_DETAIL_ID, $detailId)
                    ->where(Entity::PROVIDER, $provider)
                    ->where(Entity::MERCHANT_ID, $merchantId)
                    ->first();
    }
}
