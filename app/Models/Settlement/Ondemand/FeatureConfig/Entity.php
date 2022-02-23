<?php

namespace RZP\Models\Settlement\Ondemand\FeatureConfig;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $entity = 'settlement.ondemand.feature_config';

    protected $generateIdOnCreate = true;

    const ID_LENGTH = 14;
    const ID                              = 'id';
    const MERCHANT_ID                     = 'merchant_id';
    const PERCENTAGE_OF_BALANCE_LIMIT     = 'percentage_of_balance_limit';
    const SETTLEMENTS_COUNT_LIMIT         = 'settlements_count_limit';
    const MAX_AMOUNT_LIMIT                = 'max_amount_limit';
    const CREATED_AT                      = 'created_at';
    const UPDATED_AT                      = 'updated_at';
    const DELETED_AT                      = 'deleted_at';
    const PRICING_PERCENT                 = 'pricing_percent';
    const ES_PRICING_PERCENT              = 'es_pricing_percent';

    const FULL_ACCESS                     = 'full_access';
    const AMOUNT                          = 'amount';
    const SETTLABLE_AMOUNT                = 'settlable_amount';
    const ATTEMPTS_LEFT                   = 'attempts_left';

    const DEFAULT_SETTLEMENTS_COUNT_LIMIT     = 1000000;
    const DEFAULT_PERCENTAGE_OF_BALANCE_LIMIT = 60;
    const DEFAULT_PRICING_PERCENT             = 30;
    const DEFAULT_ES_PRICING_PERCENT          = 12;
    const DEFAULT_MAX_AMOUNT_LIMIT            = 1500000;


    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::PERCENTAGE_OF_BALANCE_LIMIT,
        self::SETTLEMENTS_COUNT_LIMIT,
        self::MAX_AMOUNT_LIMIT,
        self::PRICING_PERCENT,
        self::ES_PRICING_PERCENT
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getSettlementsCountLimit()
    {
        return $this->getAttribute(self::SETTLEMENTS_COUNT_LIMIT);
    }

    public function getMaxAmountLimit()
    {
        return $this->getAttribute(self::MAX_AMOUNT_LIMIT);
    }

    public function getPercentageOfBalanceLimit()
    {
        return $this->getAttribute(self::PERCENTAGE_OF_BALANCE_LIMIT);
    }

    public function getPricingPercent()
    {
        return $this->getAttribute(self::PRICING_PERCENT);
    }

    public function getEsPricingPercent()
    {
        return $this->getAttribute(self::ES_PRICING_PERCENT);
    }

    public function setSettlementsCountLimit($settlementsCountLimit)
    {
        $this->setAttribute(Entity::SETTLEMENTS_COUNT_LIMIT, $settlementsCountLimit);
    }

    public function setMaxAmountLimit($maxAmountLimit)
    {
        $this->setAttribute(Entity::MAX_AMOUNT_LIMIT, $maxAmountLimit);
    }

    public function setPercentageOfBalanceLimit($percentageOfBalanceLimit)
    {
        $this->setAttribute(Entity::PERCENTAGE_OF_BALANCE_LIMIT, $percentageOfBalanceLimit);
    }

    public function setPricingPercent($pricingPercent)
    {
        $this->setAttribute(Entity::PRICING_PERCENT, $pricingPercent);
    }

    public function setEsPricingPercent($esPricingPercent)
    {
        $this->setAttribute(Entity::ES_PRICING_PERCENT, $esPricingPercent);
    }
}
