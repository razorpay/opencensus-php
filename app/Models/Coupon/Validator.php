<?php

namespace RZP\Models\Coupon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    const COUPON_EXPIRY = 'coupon_expiry';

    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string',
        Entity::ENTITY_TYPE => 'required|string|max:20|in:promotion',
        Entity::CODE        => 'required|string|max:10',
        Entity::START_DATE  => 'sometimes|epoch',
        Entity::END_DATE    => 'sometimes|epoch',
        Entity::USAGE       => 'sometimes|integer',
    ];

    protected static $createValidators = [
        self::COUPON_EXPIRY,
    ];

    protected static $applyRules = [
        Entity::CODE          => 'required|string',
        Entity::MERCHANT_ID   => 'required|alpha_num|max:14',
    ];


    public function validateCouponExpiry(array $input)
    {
        if ((isset($input[Entity::START_DATE]) === true) and
            ($input[Entity::START_DATE] < time()))
        {
            throw new  Exception\BadRequestValidationFailureException(
                'Start date can not be in the past');
        }

        if ((isset($input[Entity::END_DATE]) === true) and
            ($input[Entity::END_DATE] < time()))
        {
            throw new  Exception\BadRequestValidationFailureException(
                'End date can not be in the past');
        }

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

    public function validateApplyCoupon(Merchant\Entity $merchant)
    {
        $merchantId = $this->entity->getMerchantId();

        if (($merchantId !== Merchant\Account::SHARED_ACCOUNT) and
            ($merchantId !== $merchant->getId()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_VALID_FOR_MERCHANT);
        }

        if (($this->entity->getUsage() !== null) and
            ($this->entity->getUsedCount() === $this->entity->getUsage()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_LIMIT_REACHED);
        }

        $startDate = $this->entity->getStartDate();

        if (($startDate !== null) and
            ($startDate > time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_APPLICABLE);
        }

        $endDate = $this->entity->getEndDate();

        if (($endDate !== null) and
            ($endDate < time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_EXPIRED);
        }
    }
}
