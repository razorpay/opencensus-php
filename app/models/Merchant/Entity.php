<?php

namespace Models\Merchant;

use Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                = 'id';
    const NAME              = 'name';
    const EMAIL             = 'email';
    const ACTIVATED         = 'activated';
    const ACTIVATED_AT      = 'activated_at';
    const LIVE              = 'live';
    const HOLD_FUNDS        = 'hold_funds';
    const PRICING_PLAN_ID   = 'pricing_plan_id';
    const INTERNATIONAL     = 'international';
    const BILLING_LABEL     = 'billing_label';
    const TRANSACTION_REPORT_EMAIL = 'transaction_report_email';
    const WEBSITE           = 'website';
    const CATEGORY          = 'category';

    const METHODS           = 'methods'; // Refers to methods relation and not a property;

    protected $table = \Constants\Table::MERCHANT;

    protected $entity = 'merchant';

    protected static $sign = '';

    protected static $delimiter = '';

    protected $fillable = array(
        self::ID,
        self::NAME,
        self::EMAIL,
        self::CATEGORY,
        self::WEBSITE,
        self::HOLD_FUNDS,
        self::INTERNATIONAL,
        self::BILLING_LABEL);

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED,
        self::ACTIVATED_AT,
        self::LIVE,
        self::HOLD_FUNDS,
        self::CATEGORY,
        self::WEBSITE,
        self::INTERNATIONAL,
        self::PRICING_PLAN_ID,
        self::METHODS,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected static $generators = array(
        self::LIVE,
        self::ACTIVATED);

    protected function generateLive($input)
    {
        $this->setAttribute(self::LIVE, false);
    }

    protected function generateActivated($input)
    {
        $this->setAttribute(self::ACTIVATED, false);
        $this->setAttribute(self::ACTIVATED_AT, null);
    }

    public function isActivated()
    {
        return $this->getAttribute(self::ACTIVATED);
    }

    public function isInternational()
    {
        return (boolean) $this->getAttribute(self::INTERNATIONAL);
    }

    public function isLive()
    {
        return $this->getAttribute(self::LIVE);
    }

    public function activate()
    {
        $this->setAttribute(self::ACTIVATED, true);
        $this->setAttribute(self::LIVE, true);
        $this->setAttribute(self::ACTIVATED_AT, time());
    }

    public function liveEnable()
    {
        $this->setAttribute(self::LIVE, true);
    }

    public function liveDisable()
    {
        $this->setAttribute(self::LIVE, false);
    }

    public function keys()
    {
        return $this->hasMany(
            'Models\Key\Entity');
    }

    public function payments()
    {
        return $this->hasMany(
            'Models\Payment\Entity');
    }

    public function balance()
    {
        return $this->hasOne(
            'Models\Merchant\Balance', self::ID, 'id');
    }

    public function bankAccount()
    {
        return $this->hasOne(
            'Models\Merchant\BankAccount\Entity');
    }

    public function methods()
    {
        return $this->hasOne(
            'Models\Merchant\Banks\Entity');
    }

    public function terminals()
    {
        return $this->hasMany(
            'Models\Terminal\Entity');
    }

    public function transactions()
    {
        return $this->hasMany(
            'Models\Transaction\Entity');
    }

    public function setPricingPlan($planId)
    {
        $this->setAttribute(self::PRICING_PLAN_ID, $planId);
    }

    public function getPricingPlanId()
    {
        return $this->getAttribute(self::PRICING_PLAN_ID);
    }

    public function getActivatedAttribute()
    {
        return (bool) $this->attributes[self::ACTIVATED];
    }

    public function getLiveAttribute()
    {
        return (bool) $this->attributes[self::LIVE];
    }

    public function getInternationalAttribute()
    {
        return (bool) $this->attributes[self::INTERNATIONAL];
    }

    public function holdFunds()
    {
        return (bool) $this->attributes[self::HOLD_FUNDS];
    }
}
