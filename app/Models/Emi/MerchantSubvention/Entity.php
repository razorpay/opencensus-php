<?php

namespace RZP\Models\Emi\MerchantSubvention;

use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const ID               = 'id';
    const MERCHANT_ID      = 'merchant_id';
    const EMI_PLAN_ID      = 'emi_plan_id';
    const MERCHANT_PAYBACK = 'merchant_payback';

    protected $entity  = 'emi_merchant_subvention';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::MERCHANT_PAYBACK,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::EMI_PLAN_ID,
        self::MERCHANT_PAYBACK,
        self::CREATED_AT,
        self::UPDATED_AT,
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