<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Timezone;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const NAME        = 'name';
    const PERIOD      = 'period';
    /**
     * Interval is not used only for hourly schedules.
     */
    const INTERVAL    = 'interval';
    const ANCHOR      = 'anchor';
    const HOUR        = 'hour';
    /**
     * Hourly schedules have delay in hours
     * Other schedules have delay in days
     */
    const DELAY       = 'delay';

    const ORG_ID      = 'org_id';

    const TYPE        = 'type';

    const DELETED_AT  = 'deleted_at';

    const IS_EARLY_SETTLEMENT_SCHEDULE = 'is_early_settlement_schedule';

    protected $fillable = [
        self::NAME,
        self::ORG_ID,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
        self::HOUR,
        self::DELAY,
        self::TYPE,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::ORG_ID,
        self::TYPE,
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
        self::DELAY       => 0,
        self::HOUR        => 0,
        self::ANCHOR      => null,
        self::INTERVAL    => null,
        self::NAME        => null,
        self::ORG_ID      => null,
    ];

    protected $entity = 'schedule';

    // -------------------------- Checks -----------------------

    public function isHourly()
    {
        return ($this->getPeriod() === Period::HOURLY);
    }

    public function isYearly()
    {
        return ($this->getPeriod() === Period::YEARLY);
    }

    public function hasHour()
    {
        return (($this->isHourly() === false) and
                ($this->getHour() !== 0));
    }

    // ----------------------- Modifiers -----------------------

    public function modifyAnchor(& $input)
    {
        $period = $input[self::PERIOD];

        if ((Period::isPeriodAnchored($period) === true) and
            (isset($input[self::ANCHOR]) === false))
        {
            //
            // Default anchors:
            //   - weekly: Monday (Sunday = 0)
            //   - monthly-week: First week
            //   - monthly-date: First of the month
            //   - yearly: Jan 1st
            //

            $anchor = 1;

            if ($this->isYearly() === true)
            {
                //
                // For yearly, the default anchor is set to Jan 1st.
                //
                $janFirst = Carbon::createFromDate(2016, 1, 1, Timezone::IST);

                $anchor = Anchor::getAnchorForYearly($janFirst);
            }

            $input[self::ANCHOR] = $anchor;
        }
    }

    // ----------------------- Getters -----------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
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

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
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
