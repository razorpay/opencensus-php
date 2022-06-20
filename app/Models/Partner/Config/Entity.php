<?php

namespace RZP\Models\Partner\Config;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base\PublicEntity;
use RZP\Models\Merchant\AccessMap;
use RZP\Constants as AppConstants;

class Entity extends PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID               = 'entity_id';
    const ORIGIN_ID               = 'origin_id';
    const REVISIT_AT              = 'revisit_at';
    const ORIGIN_TYPE             = 'origin_type';
    const ENTITY_TYPE             = 'entity_type';
    const TDS_PERCENTAGE          = 'tds_percentage';
    const DEFAULT_PLAN_ID         = 'default_plan_id';
    const IMPLICIT_PLAN_ID        = 'implicit_plan_id';
    const EXPLICIT_PLAN_ID        = 'explicit_plan_id';
    const COMMISSION_MODEL        = 'commission_model';
    const SETTLE_TO_PARTNER       = 'settle_to_partner';
    const IMPLICIT_EXPIRY_AT      = 'implicit_expiry_at';
    const COMMISSIONS_ENABLED     = 'commissions_enabled';
    const HAS_GST_CERTIFICATE     = 'has_gst_certificate';
    const EXPLICIT_REFUND_FEES    = 'explicit_refund_fees';
    const EXPLICIT_SHOULD_CHARGE  = 'explicit_should_charge';
    const DEFAULT_PAYMENT_METHODS = 'default_payment_methods';
    const SUB_MERCHANT_CONFIG     = 'sub_merchant_config';

    const DEFAULT_TDS_PERCENTAGE             = 500;
    const TDS_PERCENTAGE_FOR_MISSING_DETAILS = 2000;

    protected $entity             = AppConstants\Entity::PARTNER_CONFIG;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::DEFAULT_PLAN_ID,
        self::IMPLICIT_PLAN_ID,
        self::EXPLICIT_PLAN_ID,
        self::COMMISSION_MODEL,
        self::IMPLICIT_EXPIRY_AT,
        self::COMMISSIONS_ENABLED,
        self::DEFAULT_PAYMENT_METHODS,
        self::EXPLICIT_REFUND_FEES,
        self::EXPLICIT_SHOULD_CHARGE,
        self::SETTLE_TO_PARTNER,
        self::TDS_PERCENTAGE,
        self::HAS_GST_CERTIFICATE,
        self::REVISIT_AT,
        self::SUB_MERCHANT_CONFIG
    ];

    protected $public = [
        self::ID,
        self::ORIGIN_ID,
        self::ENTITY_ID,
        self::ORIGIN_TYPE,
        self::ENTITY_TYPE,
        self::DEFAULT_PLAN_ID,
        self::IMPLICIT_PLAN_ID,
        self::EXPLICIT_PLAN_ID,
        self::COMMISSION_MODEL,
        self::IMPLICIT_EXPIRY_AT,
        self::COMMISSIONS_ENABLED,
        self::DEFAULT_PAYMENT_METHODS,
        self::EXPLICIT_REFUND_FEES,
        self::EXPLICIT_SHOULD_CHARGE,
        self::SETTLE_TO_PARTNER,
        self::TDS_PERCENTAGE,
        self::HAS_GST_CERTIFICATE,
        self::REVISIT_AT,
        self::CREATED_AT,
        self::SUB_MERCHANT_CONFIG
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REVISIT_AT,
    ];

    protected $defaults = [
        self::COMMISSIONS_ENABLED     => 0,
        self::EXPLICIT_REFUND_FEES    => 0,
        self::EXPLICIT_SHOULD_CHARGE  => 0,
        self::COMMISSION_MODEL        => CommissionModel::COMMISSION,
        self::SETTLE_TO_PARTNER       => 0,
        self::TDS_PERCENTAGE          => self::DEFAULT_TDS_PERCENTAGE,
        self::HAS_GST_CERTIFICATE     => 0,
        self::DEFAULT_PAYMENT_METHODS => null,
        self::SUB_MERCHANT_CONFIG     => null,
    ];

    protected $casts = [
        self::COMMISSIONS_ENABLED     => 'bool',
        self::DEFAULT_PAYMENT_METHODS => 'array',
        self::EXPLICIT_REFUND_FEES    => 'bool',
        self::EXPLICIT_SHOULD_CHARGE  => 'bool',
        self::SETTLE_TO_PARTNER       => 'bool',
        self::TDS_PERCENTAGE          => 'int',
        self::HAS_GST_CERTIFICATE     => 'bool',
        self::SUB_MERCHANT_CONFIG     => 'array'
    ];

    protected static $unsetCreateInput = [Constants::APPLICATION_ID, Constants::PARTNER_ID];

    protected static $generators       = [self::REVISIT_AT, self::ID];

    protected $dispatchesEvents = [
        // Event 'saved' fires on insert and update both.
        'saved'   => EventSaved::class
    ];

    // --------------------- Relations ------------------
    public function entity()
    {
        return $this->morphTo();
    }

    public function origin()
    {
        return $this->morphTo();
    }

    // --------------------- GETTERS ---------------------
    public function getEntityType()
    {
        return $this->getAttribute(self::ENTITY_TYPE);
    }

    public function getEntityId()
    {
        return $this->getAttribute(self::ENTITY_ID);
    }

    public function getOriginId()
    {
        return $this->getAttribute(self::ORIGIN_ID);
    }

    public function isCommissionsEnabled() : bool
    {
        return ($this->getAttribute(self::COMMISSIONS_ENABLED) === true);
    }

    public function getDefaultPlanId()
    {
        return $this->getAttribute(self::DEFAULT_PLAN_ID);
    }

    public function getImplicitPricingPlanId()
    {
        return $this->getAttribute(self::IMPLICIT_PLAN_ID);
    }

    public function getExplicitPricingPlanId()
    {
        return $this->getAttribute(self::EXPLICIT_PLAN_ID);
    }

    public function getImplicitExpiryAt()
    {
        return $this->getAttribute(self::IMPLICIT_EXPIRY_AT);
    }

    public function getCommissionModel(): string
    {
        return $this->getAttribute(self::COMMISSION_MODEL);
    }

    public function getDefaultPaymentMethods()
    {
        return $this->getAttribute(self::DEFAULT_PAYMENT_METHODS);
    }

    public function getSubMerchantConfig()
    {
        return $this->getAttribute(self::SUB_MERCHANT_CONFIG);
    }

    public function shouldSettleToPartner(): bool
    {
        return ($this->getAttribute(self::SETTLE_TO_PARTNER) === true);
    }

    public function getTdsPercentage(): int
    {
        return $this->getAttribute(self::TDS_PERCENTAGE);
    }

    public function shouldCreditGst(): bool
    {
        return ($this->getAttribute(self::HAS_GST_CERTIFICATE) === false);
    }

    public function isDefaultConfig(): bool
    {
        return ($this->getAttribute(self::ENTITY_TYPE) === AccessMap\Entity::APPLICATION);
    }

    // --------------------- SETTERS ---------------------
    public function setEntityType($entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setOriginType($originType)
    {
        $this->setAttribute(self::ORIGIN_TYPE, $originType);
    }

    public function setOriginId($originId)
    {
        $this->setAttribute(self::ORIGIN_ID, $originId);
    }

    public function setEntityId($entityId)
    {
        $this->setAttribute(self::ENTITY_ID, $entityId);
    }

    public function setSubMerchantConfig($subMerchantConfig)
    {
        return $this->setAttribute(self::SUB_MERCHANT_CONFIG, $subMerchantConfig);
    }
    // --------------------- GENERATORS ---------------------
    public function generateRevisitAt(array $input)
    {
        if (empty($input[self::REVISIT_AT]) === true)
        {
            // current time + 1 year
            $this->setAttribute(self::REVISIT_AT, Carbon::now()->addYear()->getTimestamp());
        }
    }

    public function setImplicitPlanIdAttribute($value)
    {
        $value = $value ?: null;

        $this->attributes[self::IMPLICIT_PLAN_ID] = $value;
    }

    public function setExplicitPlanIdAttribute($value)
    {
        $value = $value ?: null;

        $this->attributes[self::EXPLICIT_PLAN_ID] = $value;
    }

    public function setImplicitExpiryAtAttribute($value)
    {
        $value = $value ?: null;

        $this->attributes[self::IMPLICIT_EXPIRY_AT] = $value;
    }

    // --------------------- END -----------------------------

    public function isExplicitRecordOnly(): bool
    {
        return ($this->getAttribute(self::EXPLICIT_SHOULD_CHARGE) === false);
    }
}
