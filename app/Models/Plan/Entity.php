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
    const PERIOD            = 'period';
    const INTERVAL          = 'interval';
    const NAME              = 'name';
    const NOTES             = 'notes';
    const MERCHANT_ID       = 'merchant_id';
    const SCHEDULE_ID       = 'schedule_id';

    // Input Keys

    // const FREQUENCY         = 'frequency';

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
        self::PERIOD,
        self::NAME,
        self::NOTES,
    ];

    protected $public = [
        self::ID,
        self::AMOUNT,
        self::CURRENCY,
        self::INTERVAL,
        self::PERIOD,
        self::NAME,
        self::NOTES,
        self::CREATED_AT
    ];

    // Used for reporting
    protected $amounts = [
        self::AMOUNT,
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::INTERVAL          => 'int',
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

    public function getPeriod()
    {
        return $this->getAttribute(self::PERIOD);
    }

    public function getCurrency()
    {
        return $this->getAttribute(self::CURRENCY);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    // --------------------- END GETTERS ---------------------

    // --------------------- RELATIONS ---------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // public function schedule()
    // {
    //     return $this->belongsTo('RZP\Models\Schedule\Entity');
    // }

    // --------------------- END RELATIONS ---------------------
}
