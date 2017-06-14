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
        Entity::CODE                => 'required|custom',
        Entity::MERCHANT_ID         => 'required|custom',
    ];

    public function validateCode($code)
    {
        if ($code === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'No Coupon Code Specified');
        }
    }

    public function validateMerchantId($merchantId)
    {
        if ($merchantId === null)
        {
            throw new Exception\BadRequestValidationFailureException(
                'No Merchant Specified for which Coupon has to be applied');
        }
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
