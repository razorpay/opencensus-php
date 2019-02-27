<?php

namespace RZP\Models\Promotion;

use RZP\Models\Base;
use RZP\Models\Pricing\DefaultPlan;
use RZP\Models\Transaction\CreditType;

class Entity extends Base\PublicEntity
{
    const NAME                    = 'name';
    const CREDIT_AMOUNT           = 'credit_amount';
    const CREDIT_TYPE             = 'credit_type';
    const SCHEDULE_ID             = 'schedule_id';
    const ITERATIONS              = 'iterations';
    const CREDITS_EXPIRE          = 'credits_expire';
    const PRICING_PLAN_ID         = 'pricing_plan_id';
    const PARTNER_ID              = 'partner_id';
    const PURPOSE                 = 'purpose';
    const CREATOR_NAME            = 'creator_name';

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
        self::PRICING_PLAN_ID,
        self::PURPOSE,
        self::CREATOR_NAME,
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
        self::PRICING_PLAN_ID,
        self::PARTNER_ID,
        self::PURPOSE,
        self::CREATOR_NAME,
    ];

    protected $defaults = [
        self::CREDIT_TYPE      => CreditType::FEE,
        self::ITERATIONS       => 1,
        self::PRICING_PLAN_ID  => DefaultPlan::PROMOTIONAL_PLAN_ID,
        self::CREDIT_AMOUNT    => 0,
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

    public function partner()
    {
        return $this->belongsTo(\RZP\Models\Merchant\Entity::class);
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

    public function getPricingPlanId()
    {
        return $this->getAttribute(self::PRICING_PLAN_ID);
    }
}
