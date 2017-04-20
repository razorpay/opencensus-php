<?php

namespace RZP\Models\Admin\Permission;

use RZP\Constants\Table;
use RZP\Models\Base\Traits\RevisionableTrait;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Org;

class Entity extends Base\Entity
{
    use RevisionableTrait;

    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const CATEGORY          = 'category';
    const ASSIGNABLE        = 'assignable';
    const ENABLE_WORKFLOW   = 'enable_workflow';

    // We sync the new permission with orgs
    const ORGS = 'orgs';

    protected $entity = 'permission';

    protected static $sign = 'perm';

    protected $generateIdOnCreate = true;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
        self::ASSIGNABLE,
        self::ENABLE_WORKFLOW,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
        self::ASSIGNABLE,
        self::ENABLE_WORKFLOW,
        self::ORGS,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
        self::ASSIGNABLE,
        self::ENABLE_WORKFLOW,
    ];

    protected $casts = [
        self::ASSIGNABLE        => 'bool',
        self::ENABLE_WORKFLOW   => 'bool',
    ];

    protected static function boot()
    {
        parent::boot();

        // Detach the permission from all roles and orgs
        static::deleting(function ($permission)
        {
            $permission->roles()->detach();
            $permission->orgs()->detach();
        });
    }

    /**
     * Returns all roles with permission in organisation
     *
     **/
    public function roles()
    {
        return $this->morphedByMany('RZP\Models\Admin\Role\Entity', 'entity', Table::PERMISSION_MAP);
    }

    /**
     * Returns organisation for permission
     **/
    public function orgs()
    {
        return $this->morphedByMany(Org\Entity::class, 'entity', Table::PERMISSION_MAP);
    }

    public function workflows()
    {
        return $this->belongsToMany('RZP\Models\Workflow\Entity', Table::WORKFLOW_PERMISSIONS);
    }

    public function canEnableWorkflow()
    {
        return ($this->getAttribute(self::ENABLE_WORKFLOW) === true);
    }
}
