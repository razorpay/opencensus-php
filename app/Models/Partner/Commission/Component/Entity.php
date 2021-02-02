<?php

namespace RZP\Models\Partner\Commission\Component;

use RZP\Models\Partner\Commission;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID                              = 'id';
    const COMMISSION_ID                   = 'commission_id';
    const MERCHANT_PRICING_PLAN_RULE_ID   = 'merchant_pricing_plan_rule_id';
    const MERCHANT_PRICING_FIXED          = 'merchant_pricing_fixed';
    const MERCHANT_PRICING_PERCENTAGE     = 'merchant_pricing_percentage';
    const MERCHANT_PRICING_AMOUNT         = 'merchant_pricing_amount';
    const COMMISSION_PRICING_PLAN_RULE_ID = 'commission_pricing_plan_rule_id';
    const COMMISSION_PRICING_FIXED        = 'commission_pricing_fixed';
    const COMMISSION_PRICING_PERCENTAGE   = 'commission_pricing_percentage';
    const COMMISSION_PRICING_AMOUNT       = 'commission_pricing_amount';
    const PRICING_FEATURE                 = 'pricing_feature';
    const PRICING_TYPE                    = 'pricing_type';


    protected $entity   = 'commission_component';

    protected $generateIdOnCreate = true;

    protected static $sign = 'comm_comp';

    protected $primaryKey  = self::ID;

    protected $public   = [
        self::ID,
        self::COMMISSION_ID,
        self::MERCHANT_PRICING_PLAN_RULE_ID,
        self::MERCHANT_PRICING_FIXED,
        self::MERCHANT_PRICING_PERCENTAGE,
        self::MERCHANT_PRICING_AMOUNT,
        self::COMMISSION_PRICING_PLAN_RULE_ID,
        self::COMMISSION_PRICING_FIXED,
        self::COMMISSION_PRICING_PERCENTAGE,
        self::COMMISSION_PRICING_AMOUNT,
        self::PRICING_FEATURE,
        self::PRICING_TYPE,
    ];

    protected $fillable = [
        self::COMMISSION_ID,
        self::MERCHANT_PRICING_PLAN_RULE_ID,
        self::MERCHANT_PRICING_FIXED,
        self::MERCHANT_PRICING_PERCENTAGE,
        self::MERCHANT_PRICING_AMOUNT,
        self::COMMISSION_PRICING_PLAN_RULE_ID,
        self::COMMISSION_PRICING_FIXED,
        self::COMMISSION_PRICING_PERCENTAGE,
        self::COMMISSION_PRICING_AMOUNT,
        self::PRICING_FEATURE,
        self::PRICING_TYPE
    ];

    public function commission()
    {
        return $this->belongsTo(Commission\Entity::class);
    }

    public function getCommissionId(): string
    {
        return $this->getAttribute(self::COMMISSION_ID);
    }

    public function getMerchantPricingAmount() : int
    {
        return $this->getAttribute(self::MERCHANT_PRICING_AMOUNT);
    }

    public function getCommissionPricingPlanRuleId(): string
    {
        return $this->getAttribute(self::COMMISSION_PRICING_PLAN_RULE_ID);
    }

    public function getCommissionPricingAmount() : int
    {
        return $this->getAttribute(self::COMMISSION_PRICING_AMOUNT);
    }

    public function getCommissionPricingPercentage() : string
    {
        return $this->getAttribute(self::COMMISSION_PRICING_PERCENTAGE);
    }

    public function getCommissionPricingFixed() : string
    {
        return $this->getAttribute(self::COMMISSION_PRICING_FIXED);
    }

    public function setCommissionPricingPlanRuleId(string $ruleId)
    {
        $this->setAttribute(self::COMMISSION_PRICING_PLAN_RULE_ID, $ruleId);
    }

    public function setCommissionPricingPercentage(int $percentage)
    {
        $this->setAttribute(self::COMMISSION_PRICING_PERCENTAGE, $percentage);
    }

    public function setCommissionPricingFixed(int $fixed)
    {
        $this->setAttribute(self::COMMISSION_PRICING_FIXED, $fixed);
    }

    public function setCommissionPricingAmount(int $amount)
    {
        $this->setAttribute(self::COMMISSION_PRICING_AMOUNT, $amount);
    }

    public function setMerchantPricingPlanRuleId(string $ruleId)
    {
        $this->setAttribute(self::MERCHANT_PRICING_PLAN_RULE_ID, $ruleId);
    }

    public function setMerchantPricingPercentage(int $percentage)
    {
        $this->setAttribute(self::MERCHANT_PRICING_PERCENTAGE, $percentage);
    }

    public function setMerchantPricingFixed(int $fixed)
    {
        $this->setAttribute(self::MERCHANT_PRICING_FIXED, $fixed);
    }

    public function setMerchantPricingAmount(int $amount)
    {
        $this->setAttribute(self::MERCHANT_PRICING_AMOUNT, $amount);
    }

    public function setCommissionPricingType(string $pricingType)
    {
        $this->setAttribute(self::PRICING_TYPE, $pricingType);
    }

    public function setPricingFeature(string $feature)
    {
        $this->setAttribute(self::PRICING_FEATURE, $feature);
    }
}