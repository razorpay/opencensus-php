<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    const NAME              = 'name';
    const DESCRIPTION       = 'description';

    protected $table = Constants\Table::PERMISSION;

    protected $entity = 'permission';

    protected static $sign = 'perm';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CREATED_AT,
    ];

    /**
     * Returns all roles with permission in organisation
     *
     **/
    public function roles()
    {

    }

    /**
     * Returns organisation for permission
     *
     **/
    public function org()
    {

    }

}
