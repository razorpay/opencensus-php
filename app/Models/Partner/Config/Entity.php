<?php

namespace RZP\Models\Partner\Config;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants;
use RZP\Models\Base\PublicEntity;

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

    protected $entity             = Constants\Entity::PARTNER_CONFIG;
}
