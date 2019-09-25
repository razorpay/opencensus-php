<?php

namespace RZP\Models\Transaction\FeeBreakup;

use App;

use RZP\Models\Base;
use RZP\Models\Pricing;
use RZP\Models\Transaction;

class Entity extends Base\PublicEntity
{
    const ID                           = 'id';
    const TRANSACTION_ID               = 'transaction_id';
    const PRICING_RULE_ID              = 'pricing_rule_id';
    const PRICING_RULE                 = 'pricing_rule';
    const NAME                         = 'name';
    const PERCENTAGE                   = 'percentage';
    const AMOUNT                       = 'amount';

    const NAME_LENGTH                  = 100;

    protected static $sign = 'fees';

    protected $entity = 'fee_breakup';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::NAME,
        self::AMOUNT,
        self::PERCENTAGE,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::AMOUNT,
        self::PERCENTAGE,
        self::TRANSACTION_ID,
        self::PRICING_RULE_ID,
        self::PRICING_RULE,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $relations = [
        self::PRICING_RULE,
    ];

    protected $publicSetters = [
        self::PRICING_RULE,
    ];

    protected $casts = [
        self::AMOUNT     => 'int',
        self::PERCENTAGE => 'int',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction\Entity::class);
    }

    public function pricingRule()
    {
        return $this->belongsTo(Pricing\Entity::class);
    }

    // ----------------------- Getters ---------------------------------------------

    public function getTransactionId()
    {
        return $this->getAttribute(self::TRANSACTION_ID);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getPercentage()
    {
        return $this->getAttribute(self::PERCENTAGE);
    }

    public function getAmount()
    {
        return $this->getAttribute(self::AMOUNT);
    }

    public function getPricingRule()
    {
        return $this->getAttribute(self::PRICING_RULE_ID);
    }

    // ----------------------- Setters ---------------------------------------------

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function setPercentage($percentage)
    {
        $this->setAttribute(self::PERCENTAGE, $percentage);
    }

    public function setAmount($amount)
    {
        $this->setAttribute(self::AMOUNT, $amount);
    }

    public function setPricingRule($ruleId)
    {
        $this->setAttribute(self::PRICING_RULE_ID, $ruleId);
    }

    public function setCreatedAt($createdAt)
    {
        $this->setAttribute(self::CREATED_AT, $createdAt);
    }

    // ----------------------- Public setters ---------------------------------

    public function setPublicPricingRuleAttribute(array & $array)
    {
        // TODO: Move to serializer
        if (app('basicauth')->isProxyOrPrivilegeAuth() === false)
        {
            unset($array[self::PRICING_RULE]);
        }
    }

}
