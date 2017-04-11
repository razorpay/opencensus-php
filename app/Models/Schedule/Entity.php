<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;
use RZP\Models\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID          = 'id';
    const NAME        = 'name';
    const MERCHANT_ID = 'merchant_id';
    const TYPE        = 'type';
    const PERIOD      = 'period';
    const INTERVAL    = 'interval';
    const ANCHOR      = 'anchor';
    const HOUR        = 'hour';
    const DELAY       = 'delay';
    const NEXT_RUN    = 'next_run';

    const DELETED_AT  = 'deleted_at';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::TYPE,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
        self::HOUR,
        self::DELAY,
        self::NEXT_RUN,
    );

    protected $public = array(
        self::ID,
        self::NAME,
        self::MERCHANT_ID,
        self::TYPE,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
        self::HOUR,
        self::DELAY,
        self::NEXT_RUN,
    );

    protected static $modifiers = array(
        self::ANCHOR,
        self::NEXT_RUN,
    );

    protected $casts = [
        self::INTERVAL => 'int',
        self::ANCHOR   => 'int',
        self::HOUR     => 'int',
        self::DELAY    => 'int',
        self::NEXT_RUN => 'int',
    ];

    protected $entity = 'schedule';

    public function updateNextRun()
    {
        $lastRun = Carbon::createFromTimestamp($this->getNextRun(), 'Asia/Kolkata');

        $currentTime = Carbon::now('Asia/Kolkata');

        $nextRun = Library::computeFutureRun($this, $currentTime, $lastRun);

        $this->setNextRun($nextRun->timestamp);
    }

    // -------------------------- Checks -------------------------------------------

    public function isHourly()
    {
        return ($this->getPeriod() === Period::HOURLY);
    }

    // ----------------------- Associations ----------------------------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity');
    }

    // ----------------------- Modifiers -------------------------------------------

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

    public function modifyNextRun(& $input)
    {
        if (isset($input[self::NEXT_RUN]) === false)
        {
            $nextRun = Carbon::today('Asia/Kolkata')->timestamp;

            $input[self::NEXT_RUN] = $nextRun;
        }
    }

    // ----------------------- Getters ---------------------------------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getType()
    {
        return $this->getAttribute(self::TYPE);
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

    public function getNextRun()
    {
        return $this->getAttribute(self::NEXT_RUN);
    }

    // ----------------------- Setters ---------------------------------------------

    public function setNextRun($nextRun)
    {
        return $this->setAttribute(self::NEXT_RUN, $nextRun);
    }

    public function setMerchantId($merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

}
