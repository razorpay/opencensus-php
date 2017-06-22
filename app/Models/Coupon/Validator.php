<?php

namespace RZP\Models\Coupon;

use Carbon\Carbon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;

class Validator extends Base\Validator
{
    const MERCHANT_ID   = 'merchant_id';
    const COUPON_EXPIRY = 'coupon_expiry';

    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string',
        Entity::ENTITY_TYPE => 'required|string|max:20',
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
        if (($this->entity->getMerchantId() !== Merchant\Account::SHARED_ACCOUNT) and
            ($this->entity->getMerchantId() !== $merchant->getId()))
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

        if (($this->entity->getStartDate() !== null) and
            ($this->entity->getStartDate() > time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_APPLICABLE);
        }

        if (($this->entity->getEndDate() !== null) and
            ($this->entity->getEndDate() < time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_EXPIRED);
        }
    }
}
