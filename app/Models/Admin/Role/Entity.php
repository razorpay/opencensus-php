<?php

namespace RZP\Models\Admin\Role;

use illuminate\database\eloquent\softdeletes;

use RZP\Models\Base;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Admin\Permission;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const ORG_ID            = 'org_id';

    const DELETED_AT        = 'deleted_at';

    protected $table = Table::ROLE;

    protected $entity = 'role';

    protected static $sign = 'role';

    protected $generateIdOnCreate = true;

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
        'permissions',
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
        'permissions',
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

    public function isDeleted()
    {
        return ($this->getAttribute(self::DELETED_AT) !== null);
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

    /**
     * Public setters
     * */
    public function setPublicOrgIdAttribute(array &$array)
    {
        $array[self::ORG_ID] = Org::getSignedId($array[self::ORG_ID]);
    }

    public function toArrayPublic()
    {
        $role = parent::toArrayPublic();

        if (isset($role['permissions']) === true)
        {
            foreach ($role['permissions'] as $key => $entity)
            {
                $role['permissions'][$key]['id'] = Permission\Entity::getSignedId($entity['id']);
            }
        }

        return $role;
    }
}
