<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_TYPE         => 'sometimes|string|in:promotion',
    ];

    public function fetchByCode(array $input)
    {
        $allowedMerchantIds = [Merchant\Account::SHARED_ACCOUNT, $input[Entity::MERCHANT_ID]];

        return $this->newQuery()
                    ->where(Entity::CODE, '=', $input[Entity::CODE])
                    ->whereIn(Entity::MERCHANT_ID, $allowedMerchantIds)
                    ->first();
    }
}
