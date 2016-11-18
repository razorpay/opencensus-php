<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base;
use RZP\Constants;

class Entity extends Base\PublicEntity
{
    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const CATEGORY          = 'category';

    protected $table = Constants\Table::PERMISSION;

    protected $entity = 'permission';

    protected static $sign = 'perm';

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
    ];

    /**
     * Returns all roles with permission in organisation
     *
     **/
    public function roles()
    {
        return $this->morphedByMany('RZP\Models\Admin\Role\Entity', 'entity', Table::PERMISSON_MAP);
    }

    /**
     * Returns organisation for permission
     *
     * TODO : Check if required
     **/
    public function org()
    {
        return $this->morphedByMany('RZP\Models\Admin\Role\Entity', 'entity', Table::PERMISSON_MAP);
    }
}
