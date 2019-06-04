<?php

namespace RZP\Models\Workflow\WorkflowPayoutAmountRules;

use Illuminate\Database\Eloquent\SoftDeletes;
use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const MERCHANT_ID    = 'merchant_id';
    const ENTITY_ID      = 'entity_id';
    const ENTITY_TYPE    = 'entity_type';
    const CONDITION      = 'condition';
    const X_AMOUNT       = 'x_amount';
    const Y_AMOUNT       = 'y_amount';
    const WORKFLOW_ID    = 'workflow_id';


    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CONDITION,
        self::X_AMOUNT,
        self::Y_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $visible = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CONDITION,
        self::X_AMOUNT,
        self::Y_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $public = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CONDITION,
        self::X_AMOUNT,
        self::Y_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $publicSetters = [
        self::MERCHANT_ID,
        self::ENTITY_ID,
        self::ENTITY_TYPE,
        self::CONDITION,
        self::X_AMOUNT,
        self::Y_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected static $sign = 'workflw';
    protected $entity = 'workflow_payout_amount_rules';


    public function getMerchantID()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getCondition()
    {
        return $this->getAttribute(self::CONDITION);
    }

    public function getXAmount()
    {
        return $this->getAttribute(self::X_AMOUNT);
    }

    public function getYAmount()
    {
        return $this->getAttribute(self::Y_AMOUNT);
    }

    public function getWorkflowID()
    {
        return $this->getAttribute(self::WORKFLOW_ID);
    }
}
