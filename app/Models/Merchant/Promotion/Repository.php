<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;

class Repository extends Base\Repository
{
    protected $entity = 'merchant_promotion';

    public function findByMerchantAndPromotionId(string $merchantId, string $promotionId)
    {
        return $this->newQuery()
                    ->merchantId($merchantId)
                    ->promotionId($promotionId)
                    ->first();
    }

    public function getCountByPromotionId(string $promotionId)
    {
        $count = $this->newQuery()
                      ->promotionId($promotionId)
                      ->count();

        return $count;
    }
}
