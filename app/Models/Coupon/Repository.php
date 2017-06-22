<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Base\JitValidator;
use RZP\Models\Merchant;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_TYPE         => 'sometimes|string',
    ];

    public function fetchByCode(array $input)
    {
        return $this->newQuery()
                    ->where(Entity::CODE, '=', $input['code'])
                    ->where(function ($query) use ($input)
                    {
                        $query->where(Entity::MERCHANT_ID, '=', Merchant\Account::SHARED_ACCOUNT)
                              ->orWhere(Entity::MERCHANT_ID, '=', $input['merchant_id']);
                    })
                    ->first();
    }
}
