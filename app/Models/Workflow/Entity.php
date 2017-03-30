<?php

namespace RZP\Models\Workflow;

use RZP\Constants\Table;
use RZP\Models\Workflow\Base;
use Illuminate\Database\Eloquent\SoftDeletes;

class Entity extends Base\Entity
{
    use SoftDeletes;

    const ID          = 'id';
    const NAME        = 'name';
    const ORG_ID      = 'org_id';
    const DELETED_AT  = 'deleted_at';

    const PERMISSIONS = 'permissions';
    const STEPS       = 'steps';

    protected static $sign = 'workflow';

    protected $entity = 'workflow';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::ORG_ID,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::ORG_ID,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
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
