<?php

namespace RZP\Models\Admin\Role;

use App;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base\Traits\RevisionableTrait;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Admin\Permission;
use RZP\Constants\Table;

class Entity extends Base\Entity
{
    use SoftDeletes;
    use RevisionableTrait;

    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const ORG_ID            = 'org_id';
    const DELETED_AT        = 'deleted_at';

    /**
     * Holds all the permissions as relation key.
     */
    const PERMISSIONS       = 'permissions';

    protected $entity = 'role';

    protected static $sign = 'role';

    protected $generateIdOnCreate = false;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
        self::PERMISSIONS,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
        self::PERMISSIONS,
    ];

    protected $publicSetters = [
        self::ID,
        self::ORG_ID,
    ];
    /**
     * Returns all admins in org for role.
     *
     **/
    public function admins()
    {
        return $this->morphedByMany('RZP\Models\Admin\Admin\Entity', 'entity', Table::ROLE_MAP);
    }

    public function groups()
    {
        return $this->morphedByMany('RZP\Models\Admin\Group\Entity', 'entity', Table::ROLE_MAP);
    }

    /**
     * Returns organisation for role
     *
     **/
    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    }

    /**
     * Returns permissions for role
     *
     **/
    public function permissions()
    {
        return $this->morphToMany('RZP\Models\Admin\Permission\Entity', 'entity', Table::PERMISSION_MAP);
    }

    /**
     * Public getters
     * */
    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function getPermissions()
    {
        $permissions = $this->permissions()->get()->toArray();

        return array_map(function($permission) {
                    return $permission['name'];
                }, $permissions);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function isSuperAdminRole()
    {
        // Default role is SuperAdmin
        if (config('heimdall.default_role_name') === $this->getName())
        {
            return true;
        }

        return false;
    }
}
