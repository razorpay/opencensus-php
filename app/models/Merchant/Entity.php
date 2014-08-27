<?php

namespace Models\Merchant;

use Models\Base;

class Entity extends Base\UniqueIdEntity
{
    const ID = 'id';
    const NAME = 'name';
    const EMAIL = 'email';
    const ACTIVATED = 'activated';
    const PRICING_PLAN_ID = 'pricing_plan_id';

    protected $table = \Constants\Table::MERCHANT;

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED);

    public $incrementing = false;

    public function isActivated()
    {
        return $this->getAttribute(self::ACTIVATED);
    }

    public function activate()
    {
        $this->setAttribute(self::ACTIVATED, true);
    }

    public function keys()
    {
        return $this->hasMany(
            'Models\Key\Entity');
    }

    public function transactions()
    {
        return $this->hasMany(
            'Models\Transaction\Entity');
    }

    public function balance()
    {
        return $this->hasOne(
            'Models\Merchant\Balance');
    }

    public function terminal()
    {
        return $this->hasOne(
            'Models\Terminal\Entity');
    }

    public function pricingPlan()
    {
        return $this->hasMany(
            'Models\Pricing\Entity');
    }

    public function setPricingPlan($planId)
    {
        $this->setAttribute(self::PRICING_PLAN_ID, $planId);
    }

    public function getPricingPlanId()
    {
        return $this->getAttribute(self::PRICING_PLAN_ID);
    }
}
