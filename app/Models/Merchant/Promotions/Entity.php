<?php

namespace RZP\Models\Merchant\Promotions;

use RZP\Exception;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID             = 'id';
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
        self::EXPIRED => 'boolean'
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::PROMOTION_ID,
        self::START_TIME,
        self::REMAINING_RUNS,
        self::EXPIRED
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
        return (int) $this->attributes[self::REMAINING_RUNS];
    }

    public function getExpired()
    {
        return $this->attributes[self::EXPIRED];
    }

    public function setExpired()
    {
        $this->setAttribute(self::EXPIRED, true);
    }

    public function updateRemainingRuns()
    {
        $remainingRuns = $this->getRemainingRuns() - 1;

        $this->setAttribute(self::REMAINING_RUNS, $remainingRuns);
    }
}
