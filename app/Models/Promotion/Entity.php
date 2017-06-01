<?php

namespace RZP\Models\Promotion;

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
    const CREDITS_EXPIRABLE   = 'credits_expirable';

    //Attribute lengths
    const NAME_LENGTH         = 50;
    const CREDIT_TYPE_LENGTH  = 10;

    protected $entity      = 'promotion';

    protected static $sign = 'promotion';

    protected $morphClass  = 'promotion';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::AMOUNT,
        self::CREDIT_TYPE,
        self::SCHEDULE_ID,
        self::ITERATIONS,
        self::CREDITS_EXPIRABLE,
    ];

    protected $visible = [
        self::NAME,
        self::AMOUNT,
        self::CREDIT_TYPE,
        self::SCHEDULE_ID,
        self::ITERATIONS,
        self::CREDITS_EXPIRABLE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $defaults = [
        self::CREDIT_TYPE      => CreditType::FEE,
        self::ITERATIONS       => 1,
    ];

    protected $casts = [
        self::AMOUNT            => 'int',
        self::ITERATIONS        => 'int',
        self::CREDITS_EXPIRABLE => 'boolean',
    ];

    protected static $modifiers = [
        self::CREDITS_EXPIRABLE,
    ];

    protected function modifyCreditsExpirable(& $input)
    {
        if (empty($input[self::CREDITS_EXPIRABLE]) === true)
        {
            $input[self::CREDITS_EXPIRABLE] = false;
        }

        $input[self::CREDITS_EXPIRABLE] = (bool) $input[self::CREDITS_EXPIRABLE];
    }

    public function schedule()
    {
        return $this->belongsTo('RZP\Models\Schedule\Entity');
    }

    public function coupons()
    {
        return $this->morphMany('RZP\Models\Coupon\Entity', 'entity');
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

    public function areCreditsExpirable()
    {
        return $this->getAttribute(self::CREDITS_EXPIRABLE);
    }
}
