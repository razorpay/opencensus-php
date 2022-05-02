<?php

namespace RZP\Models\Merchant\Promotion;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const MERCHANT_ID          = 'merchant_id';
    const PROMOTION_ID         = 'promotion_id';
    const START_TIME           = 'start_time';
    const REMAINING_ITERATIONS = 'remaining_iterations';
    const EXPIRED              = 'expired';
    const AUDIT_ID             = 'audit_id';

    protected $entity             = 'merchant_promotion';

    protected $generateIdOnCreate = true;

    protected $defaults           = [
        self::EXPIRED => false,
    ];

    protected $casts              = [
        self::EXPIRED              => 'boolean',
        self::REMAINING_ITERATIONS => 'int',
    ];

    protected $fillable           = [
        self::START_TIME,
        self::REMAINING_ITERATIONS,
        self::EXPIRED,
        self::AUDIT_ID
    ];

    protected $dates              = [
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

    public function getRemainingIterations()
    {
        return $this->attributes[self::REMAINING_ITERATIONS];
    }

    public function setExpired()
    {
        $this->setAttribute(self::EXPIRED, true);
    }

    public function decrementRemainingIterations()
    {
        $remainingIterations = $this->getAttribute(self::REMAINING_ITERATIONS);

        $remainingIterations = $remainingIterations - 1;

        $this->setAttribute(self::REMAINING_ITERATIONS, $remainingIterations);
    }
}
