<?php

namespace RZP\Models\Coupon;

use App;
use RZP\Base;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Merchant;
use RZP\Models\Promotion;
use RZP\Constants\Entity as PublicEntity;
use RZP\Models\Merchant\Detail\Constants as DetailConstants;

class Validator extends Base\Validator
{
    const COUPON_EXPIRY = 'coupon_expiry';
    const MULTIPLE_COUPON_PER_PROMOTION = 'multiple_coupon_per_promotion';

    protected static $createRules = [
        Entity::ENTITY_ID   => 'required|string',
        Entity::ENTITY_TYPE => 'required|string|max:20|in:promotion',
        Entity::CODE        => 'required|string|max:10',
        Entity::START_AT    => 'sometimes|epoch',
        Entity::END_AT      => 'sometimes|epoch',
        Entity::MAX_COUNT   => 'sometimes|integer',
        Entity::IS_INTERNAL   => 'sometimes|bool',
        Entity::ALERTS   => 'sometimes|array'
    ];

    protected static $createValidators = [
        self::COUPON_EXPIRY,
        self::MULTIPLE_COUPON_PER_PROMOTION,
    ];

    protected static $editValidators = [
        self::COUPON_EXPIRY,
    ];

    protected static $applyRules = [
        Entity::CODE          => 'required|string',
        Entity::MERCHANT_ID   => 'sometimes|alpha_num|max:14|custom',
    ];

    protected static $editRules = [
        Entity::START_AT => 'sometimes|epoch',
        Entity::END_AT  =>  'required|epoch'
    ];

    protected static $applyCouponCodeRules = [
        Entity::CODE           => 'required|string',
        DetailConstants::TOKEN => 'sometimes|string',
    ];

    /**
     * Not allowing MerchantId to be present in JSON payload if it is proxyAUTH.
     *
     * @param $attribute
     * @param $value
     * @throws Exception\BadRequestException
     */
    public function validateMerchantId($attribute, $value)
    {
        $isAdminAuth = app('basicauth')->isAdminAuth();

        if ($isAdminAuth === false)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_MERCHANT_ID_NOT_REQUIRED,
                null,
                [$attribute => $value]);
        }
    }

    public function validateMultipleCouponPerPromotion(array $input)
    {
        $entityIdPromotion =  Promotion\Entity::verifyIdAndStripSign($input['entity_id']);

        $promotion = (new Repository())->findByEntityIdAndEntityType($entityIdPromotion, $input['entity_type']);

        if($promotion !== null)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_PROMOTION_ALREADY_HAS_COUPON,
                null,
                ['input' => $input]);
        }

        return true;
    }

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

        $currentTime = time();

        if (($startAt !== null) and
            ($startAt > $currentTime))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_NOT_APPLICABLE);
        }

        $endAt = $this->entity->getEndAt();

        if (($endAt !== null) and
            ($endAt < $currentTime))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_COUPON_EXPIRED);
        }
    }
}
