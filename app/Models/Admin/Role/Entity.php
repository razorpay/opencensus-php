<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Constants\Table;

class Entity extends Base\PublicEntity
{
    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const ORG_ID            = 'org_id';

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
        self::ENTITY,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
        self::CREATED_AT,
    ];

    protected $publicSetters = [
        self::ID,
        self::ENTITY,
        self::ORG_ID,
    ];
    /**
     * Returns all admins in org for role.
     *
     **/
    public function admins()
    {
        return $this->morphByMany('RZP\Models\Admin\Admin\Entity', 'entity', Table::ROLE_MAP);
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
     * Public setters
     * */
    public function setPublicOrgIdAttribute(array &$array)
    {
        $array[self::ORG_ID] = Org::getSignedId($array[self::ORG_ID]);
    }
}
