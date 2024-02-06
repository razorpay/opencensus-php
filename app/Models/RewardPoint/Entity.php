<?php

namespace RZP\Models\RewardPoint;

use RZP\Models\Base;
use RZP\Models\Base\Traits\ExternalEntity;
use RZP\Models\Base\Traits\ExternalOwner;

class Entity extends Base\PublicEntity
{
    use ExternalOwner, ExternalEntity;

    public const ID          = 'id';
    public const ENTITY      = 'reward_point';
    public const POINTS_QTY  = 'points';
    public const POINTS_AMOUNT = 'amount';
    public const DELETED_AT  = 'deleted_at';
    public const MERCHANT_ID = 'merchant_id';
    public const CREATED_AT  = 'created_at';
    public const UPDATED_AT  =  'updated_at';

    protected static $sign = 'reward';
    protected $entity = 'reward_point';

    protected $fillable = [
        self::ID,
        self::POINTS_QTY,
        self::POINTS_AMOUNT,
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected $public = [
        self::ID,
        self::POINTS_QTY,
        self::POINTS_AMOUNT
    ];

    public function getRewardAmount()
    {
        return $this->getAttribute(self::POINTS_AMOUNT);
    }
}
