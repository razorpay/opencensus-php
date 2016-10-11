<?php

namespace RZP\Models\Plan;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Base\Traits\NotesTrait;

class Entity extends Base\PublicEntity
{
    use NotesTrait;

    const AMOUNT            = 'amount';
    const CURRENCY          = 'currency';
    const INTERVAL          = 'interval';
    const INTERVAL_COUNT    = 'interval_count';
    const NAME              = 'name';
    const NOTES             = 'notes';

    protected static $sign = 'plan';

    protected $entity = 'plan';

    protected $table = Table::PLAN;

    protected $generateIdOnCreate = true;

    protected $defaults = [
        self::NOTES => [],
    ];

    protected $fillable = [
        self::AMOUNT,
        self::CURRENCY,
        self::INTERVAL,
        self::INTERVAL_COUNT,
        self::NAME,
        self::NOTES,
    ];

    protected $public = [
        self::AMOUNT,
        self::CURRENCY,
        self::INTERVAL,
        self::INTERVAL_COUNT,
        self::NAME,
        self::NOTES,
        self::CREATED_AT
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::INTERVAL_COUNT    => 'int',
    ];

    // --------------------- GETTERS ---------------------

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getInterval()
    {
        return $this->getAttribute(self::INTERVAL);
    }
    
    public function getIntervalCount()
    {
        return $this->getAttribute(self::INTERVAL_COUNT);
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- RELATIONS ---------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // --------------------- END RELATIONS ---------------------
}