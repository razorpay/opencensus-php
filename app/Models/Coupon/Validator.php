<?php

namespace RZP\Models\Coupon;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;

class Validator extends Base\Validator
{
    const MERCHANT_ID = 'merchant_id';
    const COUPON_EXPIRY = 'coupon_expiry';

    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string|max:14',
        Entity::ENTITY_TYPE => 'required|string|max:20',
        Entity::CODE => 'required|string|max:10',
        Entity::START_DATE  => 'sometimes|epoch',
        Entity::END_DATE    => 'sometimes|epoch',
        Entity::USAGE       => 'sometimes|integer',
    ];

    protected static $applyRules = [
        Entity::CODE => 'required|string|max:10',
        self::MERCHANT_ID   => 'required|alpha_num|size:14',
    ];

    protected static $createValidators = [
        self::COUPON_EXPIRY,
    ];

    public function validateCouponExpiry($input)
    {
        if ((isset($input[Entity::START_DATE]) === false) or
            (isset($input[Entity::END_DATE]) === false))
        {
            return;
        }

        if ($input[Entity::START_DATE] > $input[Entity::END_DATE])
        {
            throw new  Exception\BadRequestValidationFailureException(
                'Start date can not be greater than end date');
        }
    }

    public function couponApplyValidator($coupon, $merchant)
    {
        if (($coupon->getUsage() !== null) and
            ($coupon->getUsedCount() === $coupon->getUsage()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_EXPIRED);
        }

        if (($coupon->getStartDate() !== null) and
            ($coupon->getStartDate() > time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_APPLICABLE);
        }

        if (($coupon->getEndDate() !== null) and
            ($coupon->getEndDate() < time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_EXPIRED);
        }

        if (($coupon->getMerchantId() !== null) and
            ($coupon->getMerchantId() !== $merchant->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_VALID_FOR_MERCHANT);
        }
    }
}
