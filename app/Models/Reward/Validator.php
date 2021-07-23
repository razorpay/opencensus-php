<?php


namespace RZP\Models\Reward;

use RZP\Base;
use Carbon\Carbon;
use RZP\Exception;
use RZP\Error\ErrorCode;


class Validator extends Base\Validator
{
    protected static $createRules = [
        'reward'         => 'required|associative_array',
        'merchant_ids'   => 'required|array',
        'merchant_ids.*' => 'filled|string|unsigned_id',
    ];

    protected static $createRewardRules = [
        Entity::NAME                            => 'sometimes|filled|string|max:50',
        Entity::ADVERTISER_ID                   => 'required|string|unsigned_id',
        Entity::PERCENT_RATE                    => 'filled|integer|min:0|max:10000',
        Entity::MAX_CASHBACK                    => 'filled|integer|min:0',
        Entity::FLAT_CASHBACK                   => 'filled|integer|min:0',
        Entity::MIN_AMOUNT                      => 'filled|integer|min:0',
        Entity::STARTS_AT                       => 'filled|epoch',
        Entity::ENDS_AT                         => 'required|epoch',
        Entity::DISPLAY_TEXT                    => 'filled|string|max:255',
        Entity::TERMS                           => 'filled|string',
        Entity::COUPON_CODE                     => 'sometimes|filled|string',
        Entity::UNIQUE_COUPON_CODES             => 'sometimes|filled|array',
        Entity::LOGO                            => 'sometimes|string',
        Entity::MERCHANT_WEBSITE_REDIRECT_LINK  => 'sometimes|string',
        Entity::BRAND_NAME                      => 'sometimes|filled|string|max:26',
    ];

    protected static $mailerRules = [
        'reward_ids'     => 'required|array',
        'merchant_ids'   => 'required|array',
        'content'        => 'required|filled|string',
        'subject'        => 'required|filled|string'
    ];

    public static $validEventTypes = [
        'coupon',
        'icon',
    ];

    public function validateRewardPeriod(array $input)
    {
        $now = Carbon::now()->getTimestamp();

        $endsAt = $input[Entity::ENDS_AT];

        $startsAt = $input[Entity::STARTS_AT] ?? $now;

        if (($startsAt < $now) or
            ($endsAt <= $now) or
            ($startsAt >= $endsAt))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REWARD_DURATION, null, null, "Invalid Reward Duration");
        }
    }

    public function validateGenericOrUniqueCoupons(array $input){
        $genericCouponPresent = isset($input[Entity::COUPON_CODE]);
        $uniqueCouponPresent = isset($input[Entity::UNIQUE_COUPON_CODES]);

        if(!$genericCouponPresent and !$uniqueCouponPresent)
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_REWARD_COUPON_MUST_BE_PRESENT, null, null, "Either generic or unique coupons must be present");
        }
    }

    protected static $updateRules = [
        'reward'         => 'required|associative_array',
        'merchant_ids'   => 'array',
    ];

    public function validateStartTime(array $input, Entity $reward)
    {
        $now = Carbon::now()->getTimestamp();

        $startsAt = $input[Entity::STARTS_AT] ;

        if (($startsAt < $now) or
            ($reward->getStartsAt() < $now))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_START_TIME, null, null, "Updated Start time and current start time should be Later than Current Time");
        }
    }

    protected static $updateRewardRules = [
        Entity::ID                              => 'required|string|unsigned_id',
        Entity::NAME                            => 'sometimes|filled|string|max:50',
        Entity::PERCENT_RATE                    => 'sometimes|filled|integer|min:0|max:10000',
        // Entity::ADVERTISER_ID                   => 'required|string',
        Entity::STARTS_AT                       => 'sometimes|filled|epoch',
        Entity::ENDS_AT                         => 'epoch',
        Entity::DISPLAY_TEXT                    => 'sometimes|filled|string|max:255',
        Entity::TERMS                           => 'sometimes|filled|string',
        Entity::COUPON_CODE                     => 'sometimes|filled|string',
        Entity::UNIQUE_COUPON_CODES             => 'sometimes|filled|array',
        Entity::LOGO                            => 'sometimes|filled|string',
        Entity::MERCHANT_WEBSITE_REDIRECT_LINK  => 'sometimes|filled|string',
    ];

    public function validateIfRewardExists($reward)
    {
        if(($reward === Null) or
            ($reward->getIsDeleted()))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REWARD, null, null, "Reward with given ID doesn't exist or deleted");
        }
    }

    public function validateRewardPeriodForUpdation(array $input)
    {
        $now = Carbon::now()->getTimestamp();

        $endsAt = $input[Entity::ENDS_AT];

        $startsAt = $input[Entity::STARTS_AT] ?? $now;

        if (($endsAt <= $now) or
            ($startsAt >= $endsAt))
        {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REWARD_DURATION, null, null, "Invalid Reward Duration");
        }
    }

    public function validateEventType($eventType)
    {
        if ( !in_array($eventType, Validator::$validEventTypes, true )) {
            throw new Exception\BadRequestException(
                ErrorCode::BAD_REQUEST_INVALID_REWARD_EVENT_TYPE, null, null, "Invalid Reward Event Type");
        }
    }
}
