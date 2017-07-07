<?php

namespace RZP\Models\Coupon;

use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Constants\Entity as PublicEntity;

class Validator extends Base\Validator
{
    const COUPON_EXPIRY = 'coupon_expiry';

    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string',
        Entity::ENTITY_TYPE => 'required|string|max:20|in:promotion',
        Entity::CODE        => 'required|string|max:10',
        Entity::START_AT    => 'sometimes|epoch',
        Entity::END_AT      => 'sometimes|epoch',
        Entity::MAX_COUNT   => 'sometimes|integer',
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
        if ((isset($input[Entity::START_AT]) === true) and
            ($input[Entity::START_AT] < time()))
        {
            throw new  Exception\BadRequestValidationFailureException(
                'Start date can not be in the past');
        }

        if ((isset($input[Entity::END_AT]) === true) and
            ($input[Entity::END_AT] < time()))
        {
            throw new  Exception\BadRequestValidationFailureException(
                'End date can not be in the past');
        }

        if ((isset($input[Entity::START_AT]) === false) or
            (isset($input[Entity::END_AT]) === false))
        {
            return;
        }

        if ($input[Entity::START_AT] > $input[Entity::END_AT])
        {
            throw new  Exception\BadRequestValidationFailureException(
                'Start date can not be greater than end date');
        }
    }

    public function validateEntityType()
    {
        $entityType = $this->entity->getEntityType();

        if ($entityType !== PublicEntity::PROMOTION)
        {
            throw new Exception\BadRequestValidationFailureException(
                'Coupon is not associated with a valid entity: ' . $entityType);
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

        if (($this->entity->getMaxCount() !== null) and
            ($this->entity->getUsedCount() === $this->entity->getMaxCount()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_LIMIT_REACHED);
        }

        $startAt = $this->entity->getStartAt();

        if (($startAt !== null) and
            ($startAt > time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_APPLICABLE);
        }

        $endAt = $this->entity->getEndAt();

        if (($endAt !== null) and
            ($endAt < time()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_EXPIRED);
        }
    }
}
