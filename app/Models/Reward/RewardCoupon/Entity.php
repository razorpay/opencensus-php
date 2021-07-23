<?php


namespace RZP\Models\Reward\RewardCoupon;

use RZP\Models\Base;


class Entity extends Base\PublicEntity
{
    const REWARD_ID     = 'reward_id';
    const COUPON_CODE   = 'coupon_code';
    const STATUS        = 'status';

    const STATUS_LENGTH = 30;

    //status constants
    const AVAILABLE     = 'available';
    const USED          = 'used';

    protected $entity      = 'reward_coupon';

    protected $fillable = [
        self::REWARD_ID,
        self::COUPON_CODE,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ENTITY,
        self::REWARD_ID,
        self::COUPON_CODE,
        self::STATUS,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function getRewardId()
    {
        return $this->getAttribute(self::REWARD_ID);
    }

    public function getCouponCode()
    {
        return $this->getAttribute(self::COUPON_CODE);
    }

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

}
