<?php

namespace RZP\Models\Partner\Config;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base\PublicEntity;
use RZP\Constants as AppConstants;

class Entity extends PublicEntity
{
    use SoftDeletes;

    const ENTITY_ID               = 'entity_id';
    const ORIGIN_ID               = 'origin_id';
    const REVISIT_AT              = 'revisit_at';
    const ORIGIN_TYPE             = 'origin_type';
    const ENTITY_TYPE             = 'entity_type';
    const DEFAULT_PLAN_ID         = 'default_plan_id';
    const IMPLICIT_PLAN_ID        = 'implicit_plan_id';
    const EXPLICIT_PLAN_ID        = 'explicit_plan_id';
    const IMPLICIT_EXPIRY_AT      = 'implicit_expiry_at';
    const COMMISSIONS_ENABLED     = 'commissions_enabled';
    const EXPLICIT_REFUND_FEES    = 'explicit_refund_fees';
    const EXPLICIT_SHOULD_CHARGE  = 'explicit_should_charge';

    protected $entity             = AppConstants\Entity::PARTNER_CONFIG;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::DEFAULT_PLAN_ID,
        self::IMPLICIT_PLAN_ID,
        self::EXPLICIT_PLAN_ID,
        self::IMPLICIT_EXPIRY_AT,
        self::COMMISSIONS_ENABLED,
        self::EXPLICIT_REFUND_FEES,
        self::EXPLICIT_SHOULD_CHARGE,
        self::REVISIT_AT,
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
        self::IMPLICIT_EXPIRY_AT,
        self::COMMISSIONS_ENABLED,
        self::EXPLICIT_REFUND_FEES,
        self::EXPLICIT_SHOULD_CHARGE,
        self::REVISIT_AT,
        self::CREATED_AT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::REVISIT_AT,
    ];

    protected $defaults = [
        self::COMMISSIONS_ENABLED    => 0,
        self::EXPLICIT_REFUND_FEES   => 0,
        self::EXPLICIT_SHOULD_CHARGE => 1,
    ];

    protected $casts = [
        self::COMMISSIONS_ENABLED    => 'bool',
        self::EXPLICIT_REFUND_FEES   => 'bool',
        self::EXPLICIT_SHOULD_CHARGE => 'bool',
    ];

    protected static $unsetCreateInput = [Constants::APPLICATION_ID, Constants::PARTNER_ID];

    protected static $generators       = [self::REVISIT_AT, self::ID];

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

    // --------------------- SETTERS ---------------------
    public function setEntityType($entityType)
    {
        $this->setAttribute(self::ENTITY_TYPE, $entityType);
    }

    public function setOriginType($originType)
    {
        $this->setAttribute(self::ORIGIN_TYPE, $originType);
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
}
