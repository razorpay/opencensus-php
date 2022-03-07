<?php

namespace RZP\Models\Promotion;

use Carbon\Carbon;

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
    const CREATOR_EMAIL           = 'creator_email';
    const PRODUCT                 = 'product';
    const EVENT_ID                = 'event_id';
    const STATUS                  = 'status';
    const START_AT                = 'start_at';
    const END_AT                  = 'end_at';
    const ACTIVATED_AT            = 'activated_at';
    const DEACTIVATED_AT          = 'deactivated_at';
    const DEACTIVATED_BY          = 'deactivated_by';
    const REFERENCE1              = 'reference1';
    const REFERENCE2              = 'reference2';
    const REFERENCE3              = 'reference3';
    const REFERENCE4              = 'reference4';
    const REFERENCE5              = 'reference5';



    //These two variables are used to create schedule for promotion
    //in case the credits need to be expired and renewed
    const CREDITS_EXPIRY_PERIOD   = 'credits_expiry_period';
    const CREDITS_EXPIRY_INTERVAL = 'credits_expiry_interval';

    const ACTIVATED                  = 'activated';
    const DEACTIVATED                = 'deactivated';
    const BANKING                    = 'banking';
    const PRIMARY                    = 'primary';
    const CREDITS                    = 'credits';

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
        self::CREATOR_EMAIL,
        self::START_AT,
        self::END_AT,
        self::DEACTIVATED_AT,
        self::DEACTIVATED_BY,
        self::DEACTIVATED_AT,
        self::STATUS,
        self::PRODUCT,
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
        self::CREATOR_EMAIL,
        self::EVENT_ID,
        self::ACTIVATED_AT,
        self::START_AT,
        self::END_AT,
        self::ACTIVATED_AT,
        self::DEACTIVATED_BY,
        self::DEACTIVATED_AT,
        self::STATUS,
        self::PRODUCT,
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
        self::START_AT,
    ];

    protected function modifyCreditsExpire(array & $input)
    {
        if (empty($input[self::CREDITS_EXPIRE]) === true)
        {
            $input[self::CREDITS_EXPIRE] = 0;
        }
    }

    protected function modifyStartAt(array & $input)
    {
        if ((empty($input[self::START_AT]) === true) and
            (isset($input[self::PRODUCT]) === true))
        {
            $time = Carbon::now()->getTimestamp();

            $input[self::START_AT] = $time;
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

    public function event()
    {
        return $this->belongsTo(Event\Entity::class);
    }

// ----------------------- Getters ---------------------------------------------

    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

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

    public function getProduct()
    {
        return $this->getAttribute(self::PRODUCT);
    }

    public function getStartAt()
    {
        return $this->getAttribute(self::START_AT);
    }

    public function getEndAt()
    {
        return $this->getAttribute(self::END_AT);
    }
    // setters

    public function setActivatedAt($time = null)
    {
        $this->setAttribute(self::ACTIVATED_AT, $time);
    }

    public function setDeactivatedAt($time = null)
    {
        $this->setAttribute(self::DEACTIVATED_AT, $time);
    }

    public function setStatus(string $status)
    {
        $this->setAttribute(self::STATUS, $status);

        $timestampKey = $status . '_at';

        $currentTime = Carbon::now()->getTimestamp();

        $this->setAttribute($timestampKey, $currentTime);
    }

    public function setProduct($product)
    {
        $this->setAttribute(self::PRODUCT, $product);
    }

    public function setStartAt($time = null)
    {
        if ($time === null)
        {
            $time = Carbon::now()->getTimestamp();
        }

        $this->setAttribute(self::START_AT, $time);
    }

    public function setEndAt($time = null)
    {
        $this->setAttribute(self::END_AT, $time);
    }
}
