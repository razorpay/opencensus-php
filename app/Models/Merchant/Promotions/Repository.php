<?php

namespace RZP\Models\Merchant\Promotions;

use RZP\Exception;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Merchant\Promotions;

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
}
