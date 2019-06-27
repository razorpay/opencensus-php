<?php

namespace RZP\Models\Workflow;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants\Table;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\PayoutAmountRules;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const ID          = 'id';
    const NAME        = 'name';
    const ORG_ID      = 'org_id';
    const DELETED_AT  = 'deleted_at';

    const PERMISSIONS         = 'permissions';
    const STEPS               = 'steps';
    const PAYOUT_AMOUNT_RULES = 'payoutAmountRules';
    const LEVELS              = 'levels';

    protected static $sign = 'workflow';

    protected $entity = 'workflow';

    protected $generateIdOnCreate = false;

    protected $embeddedRelations = [
        self::STEPS,
        self::PERMISSIONS,
        self::PAYOUT_AMOUNT_RULES,
    ];

    protected $fillable = [
        self::ID,
        self::NAME,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::ORG_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STEPS,
        self::PERMISSIONS,
        self::PAYOUT_AMOUNT_RULES,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STEPS,
        self::PERMISSIONS,
        self::PAYOUT_AMOUNT_RULES,
    ];

    protected $publicSetters = [
        self::ID,
        self::ORG_ID,
    ];

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    }

    public function steps()
    {
        return $this->hasMany('RZP\Models\Workflow\Step\Entity');
    }

    public function payoutAmountRules()
    {
        return $this->hasMany(PayoutAmountRules\Entity::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(
            'RZP\Models\Admin\Permission\Entity',
            Table::WORKFLOW_PERMISSION);
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }
}
