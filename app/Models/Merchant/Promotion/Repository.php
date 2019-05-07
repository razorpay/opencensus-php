<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_promotion';

    protected $appFetchParamRules = [
        Entity::PROMOTION_ID     => 'sometimes|alpha_num',
    ];

    public function findByMerchantAndPromotionId(string $merchantId, string $promotionId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->where(Entity::PROMOTION_ID, $promotionId)
                    ->first();
    }

    public function getCountByPromotionId(string $promotionId)
    {
        $count = $this->newQuery()
                      ->where(Entity::PROMOTION_ID, $promotionId)
                      ->count();

        return $count;
    }

    public function getByMerchantId(string $merchantId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->get();
    }
}
