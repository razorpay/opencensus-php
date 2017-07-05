<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID    = 'merchant_id';
    const PROMOTION_ID   = 'promotion_id';
    const START_TIME     = 'start_time';
    const REMAINING_RUNS = 'remaining_runs';
    const EXPIRED        = 'expired';

    protected $entity = 'merchant_promotion';

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::EXPIRED => false
    ];

    protected $casts = [
        self::EXPIRED        => 'boolean',
        self::REMAINING_RUNS => 'int',
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::PROMOTION_ID,
        self::START_TIME,
        self::REMAINING_RUNS,
        self::EXPIRED
    ];

    protected $dates = [
        self::START_TIME,
    ];

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    public function promotion()
    {
        return $this->belongsTo('RZP\Models\Promotion\Entity');
    }

    public function getRemainingRuns()
    {
        return $this->attributes[self::REMAINING_RUNS];
    }

    public function setExpired()
    {
        $this->setAttribute(self::EXPIRED, true);
    }

    public function decrementRemainingRuns()
    {
        $this->decrement(self::REMAINING_RUNS);
    }
}
