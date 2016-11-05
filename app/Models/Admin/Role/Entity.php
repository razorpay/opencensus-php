<?php

namespace RZP\Models\Admin\Role;

use RZP\Models\Base;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const ORG_ID            = 'org_id';

    protected $table = Constants\Table::ROLE;

    protected $entity = 'role';

    protected static $sign = 'role';

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
        self::CREATED_AT,
    ];

    /**
     * Returns all admins in org for role.
     *
     **/
    public function admins()
    {
        return $this->belongsToMany('RZP\Models\Admin\Admin\Entity', Constants\Table::ADMIN_ROLE, 'role_id', 'admin_id');
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
        return $this->belongsToMany('RZP\Models\Admin\Permission\Entity', Constants\Table::PERMISSION_MAP, 'role_id', 'permission_id');
    }
}
