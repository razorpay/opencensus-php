<?php

namespace RZP\Models\Merchant\EmiPlans;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const ID               = 'id';
    const MERCHANT_ID      = 'merchant_id';
    const EMI_PLAN_ID      = 'emi_plan_id';

    protected $entity  = 'merchant_emi_plans';

    protected $generateIdOnCreate = true;

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::EMI_PLAN_ID,
        self::CREATED_AT,
    ];

    // ----------------------- Relations -----------------------

    public function merchant()
    {
        return $this->belongsTo('RZP\Models\Merchant\Entity', self::MERCHANT_ID, self::ID);
    }

    public function emiPlan()
    {
        return $this->belongsTo('RZP\Models\Emi\Entity', self::EMI_PLAN_ID, self::ID);
    }
}
