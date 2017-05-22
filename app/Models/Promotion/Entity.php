<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Transaction\CreditType;

class Entity extends Base\PublicEntity
{
    const NAME                = 'name';
    const AMOUNT              = 'amount';
    const CREDIT_TYPE         = 'credit_type';
    const SCHEDULE_ID         = 'schedule_id';
    const ITERATIONS          = 'iterations';
    const CREDITS_EXPIRE      = 'credits_expire';

    //Attribute lengths
    const NAME_LENGTH               = 50;

    protected $entity      = 'promotion';

    protected static $sign = 'promotion';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::AMOUNT,
        self::CREDIT_TYPE,
        self::SCHEDULE_ID,
        self::ITERATIONS,
        self::CREDITS_EXPIRE,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::AMOUNT,
        self::CREDITS_EXPIRE,
    ];

    protected $visible = [
        self::NAME,
        self::AMOUNT,
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
        self::AMOUNT         => 'int',
        self::ITERATIONS     => 'int',
        self::CREDITS_EXPIRE => 'bool',
    ];

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity');
    }

    public function coupons()
    {
        return $this->morphToMany('RZP\Models\Coupon\Entity', 'entity');
    }

// ----------------------- Getters ---------------------------------------------

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getCreditType()
    {
        return $this->getAttribute(self::CREDIT_TYPE);
    }

    public function getIterations()
    {
        return $this->getAttribute(self::ITERATIONS);
    }

    public function doCreditsExpire()
    {
        return $this->getAttribute(self::CREDITS_EXPIRE);
    }



// ----------------------- Setters ---------------------------------------------
    //will add later if needed
}
