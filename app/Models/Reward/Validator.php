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
        Entity::MIN_AMOUNT                      => 'fille   d|integer|min:0',
        Entity::STARTS_AT                       => 'filled|epoch',
        Entity::ENDS_AT                         => 'required|epoch',
        Entity::DISPLAY_TEXT                    => 'filled|string|max:255',
        Entity::TERMS                           => 'filled|string',
        Entity::COUPON_CODE                     => 'required|string',
        Entity::LOGO                            => 'sometimes|string',
        Entity::MERCHANT_WEBSITE_REDIRECT_LINK  => 'sometimes|string',
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
}
