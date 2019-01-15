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
        Entity::CODE                => 'sometimes|string',
    ];

    public function findByEntityIdAndEntityType(string $entityId,string $entityType)
    {
        return $this->newQuery()
                    ->where(Entity::ENTITY_ID, '=', $entityId)
                    ->where(Entity::ENTITY_TYPE,'=',$entityType)
                    ->first();
    }

    public function fetchByCodeWithRelations(string $code, string $merchantId)
    {
        $allowedMerchantIds = [Merchant\Account::SHARED_ACCOUNT, $merchantId];

        return $this->newQuery()
                    ->where(Entity::CODE, '=', $code)
                    ->whereIn(Entity::MERCHANT_ID, $allowedMerchantIds)
                    ->with(['source'])
                    ->first();
    }
}
