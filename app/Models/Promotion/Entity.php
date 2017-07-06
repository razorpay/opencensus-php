<?php

namespace RZP\Models\Promotion;

use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Transaction\CreditType;

class Entity extends Base\PublicEntity
{
    const NAME                    = 'name';
    const CREDIT_AMOUNT           = 'credit_amount';
    const CREDIT_TYPE             = 'credit_type';
    const SCHEDULE_ID             = 'schedule_id';
    const ITERATIONS              = 'iterations';
    const CREDITS_EXPIRE          = 'credits_expire';

    //These two variables are used to create schedule for promotion
    //in case the credits need to be expired and renewed
    const CREDITS_EXPIRY_PERIOD   = 'credits_expiry_period';
    const CREDITS_EXPIRY_INTERVAL = 'credits_expiry_interval';

    protected $entity      = 'promotion';

    protected static $sign = 'prom';

    protected $morphClass  = 'promotion';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::CREDIT_AMOUNT,
        self::CREDIT_TYPE,
        self::ITERATIONS,
        self::CREDITS_EXPIRE,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::CREDIT_AMOUNT,
        self::CREDIT_TYPE,
        self::SCHEDULE_ID,
        self::ITERATIONS,
        self::CREDITS_EXPIRE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::CREDIT_TYPE      => CreditType::FEE,
        self::ITERATIONS       => 1,
    ];

    protected $casts = [
        self::CREDIT_AMOUNT  => 'int',
        self::ITERATIONS     => 'int',
        self::CREDITS_EXPIRE => 'bool',
    ];

    protected static $modifiers = [
        self::CREDITS_EXPIRE,
    ];

    protected function modifyCreditsExpire(array & $input)
    {
        if (empty($input[self::CREDITS_EXPIRE]) === true)
        {
            $input[self::CREDITS_EXPIRE] = 0;
        }
    }

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity');
    }

    public function coupons()
    {
        return $this->morphMany('RZP\Models\Coupon\Entity', 'source');
    }

// ----------------------- Getters ---------------------------------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getCreditAmount()
    {
        return $this->getAttribute(self::CREDIT_AMOUNT);
    }

    public function getCreditType()
    {
        return $this->getAttribute(self::CREDIT_TYPE);
    }

    public function getIterations()
    {
        return $this->getAttribute(self::ITERATIONS);
    }

    public function doCreditsExpire(): bool
    {
        return $this->getAttribute(self::CREDITS_EXPIRE);
    }
}
