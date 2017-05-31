<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const NAME        = 'name';
    const MERCHANT_ID = 'merchant_id';
    const PERIOD      = 'period';
    const INTERVAL    = 'interval';
    const ANCHOR      = 'anchor';
    const HOUR        = 'hour';
    const DELAY       = 'delay';
    const NEXT_RUN    = 'next_run';

    const DELETED_AT  = 'deleted_at';

    protected $fillable = [
        self::NAME,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
        self::HOUR,
        self::DELAY,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::MERCHANT_ID,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
        self::HOUR,
        self::DELAY,
    ];

    protected static $modifiers = [
        self::ANCHOR,
    ];

    protected $casts = [
        self::INTERVAL => 'int',
        self::ANCHOR   => 'int',
        self::HOUR     => 'int',
        self::DELAY    => 'int',
    ];

    protected $defaults = [
        self::DELAY     => 0,
        self::HOUR      => 0,
        self::ANCHOR    => null,
        self::INTERVAL  => null,
        self::NAME      => null,
    ];

    protected $entity = 'schedule';

    // -------------------------- Checks -----------------------

    public function isHourly()
    {
        return ($this->getPeriod() === Period::HOURLY);
    }

    public function hasHour()
    {
        return (($this->isHourly() === false) and
                ($this->getHour() !== 0));
    }

    // ----------------------- Relations -----------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // ----------------------- Modifiers -----------------------

    public function modifyAnchor(& $input)
    {
        $period = $input[self::PERIOD];

        $anchoredPeriods = Period::ANCHORED_PERIODS;

        if ((in_array($period, $anchoredPeriods, true) === true) and
            isset($input[self::ANCHOR]) === false)
        {
            // For weekly periods, default anchor is Monday (Sunday is zero)
            // For monthly-week periods, default anchor is first week
            // For monthly-date periods, default anchor is 1st of the month
            $input[self::ANCHOR] = 1;
        }
    }

    // ----------------------- Getters -----------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getPeriod()
    {
        return $this->getAttribute(self::PERIOD);
    }

    public function getInterval()
    {
        return $this->getAttribute(self::INTERVAL);
    }

    public function getAnchor()
    {
        return $this->getAttribute(self::ANCHOR);
    }

    public function getHour()
    {
        return $this->getAttribute(self::HOUR);
    }

    public function getDelay()
    {
        return $this->getAttribute(self::DELAY);
    }

    // ----------------------- Setters -----------------------

    public function setAnchor($anchor)
    {
        $this->setAttribute(self::ANCHOR, $anchor);
    }
}
