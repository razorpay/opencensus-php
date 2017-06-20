<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_promotion';

    public function findByMerchantAndPromotionId(string $merchantId, string $promotionId)
    {
        return $this->newQuery()
                    ->where(Entity::MERCHANT_ID, '=', $merchantId)
                    ->where(Entity::PROMOTION_ID, '=', $promotionId)
                    ->first();
    }

    public function findUsedCountByPromotionId(string $promotionId)
    {
        $count = $this->newQuery()
                      ->where(Entity::PROMOTION_ID, '=', $promotionId)
                      ->count();

        return $count;
    }
}
