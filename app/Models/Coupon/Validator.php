<?php

namespace RZP\Models\Coupon;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string|max:14',
        Entity::ENTITY_TYPE => 'required|string|max:20',
        Entity::COUPON_CODE => 'required|string|max:10',
        Entity::START_DATE  => 'sometimes|integer',
        Entity::END_DATE    => 'sometimes|integer',
        Entity::USAGE       => 'sometimes|integer',
    ];

    protected static $applyRules = [
        Entity::COUPON_CODE => 'required|string|max:10',
        'merchant_id'       => 'required|alpha_num|size:14',
    ];

    public function couponApplyValidator($coupon, $merchantId)
    {
        if (($coupon->getUsage() !== null) and
            ($coupon->getUsedCount() === $coupon->getUsage()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_ALREADY_USED);
        }

        if (($coupon->getMerchantId() !== null) and
            ($coupon->getMerchantId() !== $merchantId))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_VALID_FOR_MERCHANT);
        }
    }
}
