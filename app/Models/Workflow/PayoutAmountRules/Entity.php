<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Workflow\Base;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const MERCHANT_ID = 'merchant_id';
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const CONDITION   = 'condition';
    const X_AMOUNT    = 'x_amount';
    const Y_AMOUNT    = 'y_amount';
    const WORKFLOW_ID = 'workflow_id';

    protected $generateIdOnCreate = false;

    protected $entity = 'workflow_payout_amount_rules';

    protected $fillable = [
        self::MERCHANT_ID,
        self::CONDITION,
        self::X_AMOUNT,
        self::Y_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $visible = [
        self::MERCHANT_ID,
        self::CONDITION,
        self::X_AMOUNT,
        self::Y_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $public = [
        self::WORKFLOW_ID,
        self::MERCHANT_ID,
        self::CONDITION,
        self::X_AMOUNT,
        self::Y_AMOUNT,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $amounts = [
        self::X_AMOUNT,
        self::Y_AMOUNT,
    ];


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
