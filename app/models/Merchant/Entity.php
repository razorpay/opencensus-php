<?php

namespace Models\Merchant;

use Models\Base;
use Models\Pricing\Service as PricingService;

class Entity extends Base\PublicEntity
{
    const ID                        = 'id';
    const NAME                      = 'name';
    const EMAIL                     = 'email';
    const ACTIVATED                 = 'activated';
    const ACTIVATED_AT              = 'activated_at';
    const LIVE                      = 'live';
    const HOLD_FUNDS                = 'hold_funds';
    const PRICING_PLAN_ID           = 'pricing_plan_id';
    const INTERNATIONAL             = 'international';
    const BILLING_LABEL             = 'billing_label';
    const TRANSACTION_REPORT_EMAIL  = 'transaction_report_email';
    const RECEIPT_EMAIL_ENABLED     = 'receipt_email_enabled';
    const WEBSITE                   = 'website';
    const CATEGORY                  = 'category';

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
        self::BILLING_LABEL,
        self::RECEIPT_EMAIL_ENABLED,
        self::TRANSACTION_REPORT_EMAIL,
    );

    protected $public = array(
        self::ID,
        self::ENTITY,
        self::NAME,
        self::EMAIL,
        self::ACTIVATED,
        self::ACTIVATED_AT,
        self::LIVE,
        self::HOLD_FUNDS,
        self::PRICING_PLAN_ID,
        self::WEBSITE,
        self::CATEGORY,
        self::INTERNATIONAL,
        self::BILLING_LABEL,
        self::TRANSACTION_REPORT_EMAIL,
        self::METHODS,
        self::CREATED_AT,
        self::UPDATED_AT);

    protected static $generators = array(
        self::LIVE,
        self::ACTIVATED,
        self::RECEIPT_EMAIL_ENABLED);

    protected function generateLive($input)
    {
        $this->setAttribute(self::LIVE, false);
    }

    protected function generateActivated($input)
    {
        $this->setAttribute(self::ACTIVATED, false);
        $this->setAttribute(self::ACTIVATED_AT, null);
    }

    protected function generateReceiptEmailEnabled($input)
    {
        $this->setAttribute(self::RECEIPT_EMAIL_ENABLED, true);
    }

    protected function generateTrnasactionReceiptEmail($input)
    {
        $this->setAttribute(self::TRANSACTION_REPORT_EMAIL, $input[self::EMAIL]);
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

    public function getPricingPlan()
    {
        return (new PricingService)->getPricingPlanById($this->getPricingPlanId());
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

    public function getWebsite()
    {
        return $this->attributes[self::WEBSITE];
    }

    public function getBillingLabel()
    {
        return $this->attributes[self::BILLING_LABEL];
    }

    public function holdFunds()
    {
        return (bool) $this->attributes[self::HOLD_FUNDS];
    }

    public function isReceiptEmailsEnabled()
    {
        return (bool) $this->attribute[self::RECEIPT_EMAIL_ENABLED];
    }

    public function getRedactedAccountNumber()
    {
        $ac = $this->bankAccount->getAccountNumber();

        //
        // How many times should we repeat the redacted portion
        // This does not give a precise result,
        // but it looks good in groups of 4
        //

        $repeat = ceil((strlen($ac) - 4)/4);

        return str_repeat('XXXX-', $repeat) . substr($ac, -4);
    }
}
