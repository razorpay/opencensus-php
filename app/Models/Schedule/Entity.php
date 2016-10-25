<?php

namespace RZP\Models\Schedule;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const ID          = 'id';
    const NAME        = 'name';
    const MERCHANT_ID = 'merchant_id';
    const TYPE        = 'type';
    const PERIOD      = 'period';
    const INTERVAL    = 'interval';
    const ANCHOR      = 'anchor';
    const DELAY       = 'delay';
    const NEXT_RUN    = 'next_run';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::TYPE,
        self::PERIOD,
        self::INTERVAL,
        self::ANCHOR,
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
        self::DELAY    => 'int',
        self::NEXT_RUN => 'int',
    ];

    protected $table = Table::SCHEDULE;

    protected $entity = 'schedule';

    /**
     * Create a new Eloquent Collection instance.
     *
     * @param  array  $models
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function newCollection(array $models = array())
    {
        return new Collection($models);
    }

    public function updateNextRun()
    {
        $lastRun = Carbon::now('Asia/Kolkata')->timestamp;

        $nextRun = Library::getNextApplicableTime($lastRun, $this);

        $this->setNextRun($nextRun);
    }

    // ----------------------- Associations ----------------------------------------

    public function merchant()
    {
        return $this->belongsTo(
            'RZP\Models\Merchant\Entity', self::MERCHANT_ID);
    }

    // ----------------------- Modifiers -------------------------------------------

    public function modifyAnchor(& $input)
    {
        $period = $input[self::PERIOD];

        $anchoredPeriods = array_keys(Steps::ANCHORED_STEPS);

        if ((in_array($period, $anchoredPeriods) === true) and
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
            $input[self::NEXT_RUN] = 0;
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