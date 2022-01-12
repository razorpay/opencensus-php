<?php

namespace RZP\Models\Settlement\EarlySettlementFeaturePeriod;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    protected $entity = 'early_settlement_feature_period';

    protected $generateIdOnCreate = true;

    const ID_LENGTH = 14;
    const ID                              = 'id';
    const MERCHANT_ID                     = 'merchant_id';
    const ENABLE_DATE                     = 'enable_date';
    const DISABLE_DATE                    = 'disable_date';
    const FEATURE                         = 'feature';
    const INITIAL_ONDEMAND_PRICING        = 'initial_ondemand_pricing';
    const INITIAL_SCHEDULE_ID             = 'initial_schedule_id';

    const FULL_ACCESS                     = 'full_access';
    const AMOUNT_LIMIT                    = 'amount_limit';
    const ES_PRICING                      = 'es_pricing';

    protected $fillable = [
        self::ID,
        self::MERCHANT_ID,
        self::DISABLE_DATE,
        self::ENABLE_DATE,
        self::FEATURE,
        self::INITIAL_ONDEMAND_PRICING,
        self::INITIAL_SCHEDULE_ID
    ];

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getEnableDate()
    {
        return $this->getAttribute(self::ENABLE_DATE);
    }

    public function getDisableDate()
    {
        return $this->getAttribute(self::DISABLE_DATE);
    }

    public function getFeature()
    {
        return $this->getAttribute(self::FEATURE);
    }

    public function getInitialScheduleId()
    {
        return $this->getAttribute(self::INITIAL_SCHEDULE_ID);
    }

    public function getInitialOndemandPricing()
    {
        return $this->getAttribute(self::INITIAL_ONDEMAND_PRICING);
    }

    public function setMerchantId($merchantId)
    {
        $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    public function setEnableDate($enableDate)
    {
        $this->setAttribute(self::ENABLE_DATE, $enableDate);
    }

    public function setDisableDate($disableDate)
    {
        $this->setAttribute(self::DISABLE_DATE, $disableDate);
    }

    public function setFeature($feature)
    {
        $this->setAttribute(self::FEATURE, $feature);
    }

    public function setAmountLimit($amountLimit)
    {
        $this->setAttribute(self::AMOUNT_LIMIT, $amountLimit);
    }

    public function setInitialOndemandPricing($ondemandPricing)
    {
        $this->setAttribute(self::INITIAL_ONDEMAND_PRICING, $ondemandPricing);
    }
}
