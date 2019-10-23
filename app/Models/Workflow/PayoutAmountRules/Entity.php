<?php

namespace RZP\Models\Workflow\PayoutAmountRules;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Workflow;
use RZP\Models\Workflow\Base;

/**
 * Class Entity
 *
 * @package RZP\Models\Workflow\PayoutAmountRules
 *
 * @property Workflow\Repository $workflow
 */
class Entity extends Base\Entity
{
    use SoftDeletes;

    const MERCHANT_ID = 'merchant_id';
    const ENTITY_ID   = 'entity_id';
    const ENTITY_TYPE = 'entity_type';
    const CONDITION   = 'condition';
    const MIN_AMOUNT  = 'min_amount';
    const MAX_AMOUNT  = 'max_amount';
    const WORKFLOW_ID = 'workflow_id';

    // Relations
    const WORKFLOW = 'workflow';

    protected $generateIdOnCreate = false;

    public $incrementing = true;

    protected $entity = 'workflow_payout_amount_rules';

    protected $fillable = [
        self::MERCHANT_ID,
        self::CONDITION,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $visible = [
        self::MERCHANT_ID,
        self::CONDITION,
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::WORKFLOW_ID,
        self::WORKFLOW,
    ];

    protected $public = [
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
        self::WORKFLOW_ID,
    ];

    protected $dates = [
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT,
    ];

    protected $amounts = [
        self::MIN_AMOUNT,
        self::MAX_AMOUNT,
    ];

    protected $casts = [
        self::MIN_AMOUNT => 'int',
        self::MAX_AMOUNT => 'int',
    ];

    public function workflow()
    {
        return $this->belongsTo(Workflow\Entity::class);
    }

    public function getCondition()
    {
        return $this->getAttribute(self::CONDITION);
    }

    public function getMinAmount()
    {
        return $this->getAttribute(self::MIN_AMOUNT);
    }

    public function getMaxAmount()
    {
        return $this->getAttribute(self::MAX_AMOUNT);
    }
}
