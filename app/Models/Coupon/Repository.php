<?php

namespace RZP\Models\Coupon;

use RZP\Models\Base;
use RZP\Base\JitValidator;

class Repository extends Base\Repository
{
    protected $entity = 'coupon';

    protected $appFetchParamRules = [
        Entity::MERCHANT_ID         => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_ID           => 'sometimes|alpha_num|max:14',
        Entity::ENTITY_TYPE         => 'sometimes|string',
    ];

    protected $applyCouponRules = [
        Entity::CODE                => 'required|string|max:14',
        Entity::MERCHANT_ID         => 'required|alpha_num|max:14',
    ];

    public function messages()
    {
        return [
            Entity::CODE        => 'No Coupon Code Specified',
            Entity::MERCHANT_ID => 'No Merchant Specified',
        ];
    }

    public function fetchByCode(array $input)
    {
        (new JitValidator)->rules($this->applyCouponRules)
                          ->input($input)
                          ->caller($this)
                          ->validate();


        return $this->newQuery()
                    ->where(Entity::CODE, '=', $input['code'])
                    ->first();
    }
}
