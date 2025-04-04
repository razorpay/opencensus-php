<?php

namespace RZP\Models\Workflow;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Constants\Table;
use RZP\Models\Base\Traits\LazyLoadingRelationFetch;
use RZP\Models\Merchant;
use RZP\Models\Workflow\Base;
use RZP\Models\Workflow\PayoutAmountRules;
use RZP\Models\Merchant\Acs\Traits\AsvGetAttribute;

class Entity extends Base\Entity
{
    use SoftDeletes, AsvGetAttribute;
    use LazyLoadingRelationFetch;

    const ID          = 'id';
    const NAME        = 'name';
    const ORG_ID      = 'org_id';
    const MERCHANT_ID = 'merchant_id';
    const DELETED_AT  = 'deleted_at';
    const CANARY_ENABLED = 'canary_enabled';
    const CANARY_PERCENTAGE = 'canary_percentage';

    const PERMISSIONS        = 'permissions';
    const STEPS              = 'steps';
    const PAYOUT_AMOUNT_RULE = 'payoutAmountRule';
    const LEVELS             = 'levels';

    protected static $sign = 'workflow';

    protected $entity = 'workflow';

    protected $generateIdOnCreate = false;

    protected $embeddedRelations = [
        self::STEPS,
        self::PERMISSIONS,
        self::PAYOUT_AMOUNT_RULE,
    ];

    protected $fillable = [
        self::ID,
        self::NAME,
        self::CANARY_ENABLED,
        self::CANARY_PERCENTAGE,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::ORG_ID,
        self::MERCHANT_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::STEPS,
        self::PERMISSIONS,
        self::PAYOUT_AMOUNT_RULE,
        self::CANARY_PERCENTAGE,
        self::CANARY_ENABLED,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::MERCHANT_ID,
        self::STEPS,
        self::PERMISSIONS,
        self::PAYOUT_AMOUNT_RULE,
        self::CANARY_PERCENTAGE,
        self::CANARY_ENABLED,
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

    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    public function steps()
    {
        return $this->hasMany('RZP\Models\Workflow\Step\Entity');
    }

    public function payoutAmountRule()
    {
        return $this->hasOne(PayoutAmountRules\Entity::class);
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

    public function getCanaryPercentage()
    {
        return $this->getAttribute(self::CANARY_PERCENTAGE) ?? 0;
    }

    public function isCanaryEnabled()
    {
        return $this->getAttribute(self::CANARY_ENABLED) ?? false;
    }
}
