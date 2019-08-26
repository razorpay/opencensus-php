<?php

namespace RZP\Models\Admin\Permission;

use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;
use RZP\Models\Base\Traits\HardDeletes;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\Entity
{
    use HardDeletes;
    use RevisionableTrait;

    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const CATEGORY          = 'category';
    const ASSIGNABLE        = 'assignable';

    // Input field
    const WORKFLOW_ORGS     = 'workflow_orgs';

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
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
        self::ASSIGNABLE,
        self::ORGS,
        self::WORKFLOW_ORGS,
        self::ROLES,
    ];

    protected $diff = [
        self::NAME
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
        self::ASSIGNABLE,
        self::WORKFLOW_ORGS,
    ];

    protected $casts = [
        self::ASSIGNABLE        => 'bool',
    ];

    protected $publicSetters = [
        self::ID,
    ];

    protected $embeddedRelations = [
        self::ORGS,
        self::WORKFLOW_ORGS,
    ];

    protected static function boot()
    {
        parent::boot();

        // Detach the permission from all roles and orgs
        static::deleting(function ($permission)
        {
            $permission->roles()->detach();
            $permission->orgs()->detach();
            $permission->workflows()->detach();
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

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    /**
     * Returns organisation for permission
     **/
    public function orgs()
    {
        return $this->morphedByMany(Org\Entity::class, 'entity', Table::PERMISSION_MAP);
    }

    public function workflow_orgs()
    {
        return $this->orgs()->where('enable_workflow', '=', 1);
    }

    public function workflows()
    {
        return $this->belongsToMany('RZP\Models\Workflow\Entity', Table::WORKFLOW_PERMISSION);
    }

    public function getRelationsForDiffer() : array
    {
        return [
            self::ORGS,
        ];
    }
}
