<?php

namespace RZP\Models\Admin\Permission;

use RZP\Models\Base\Traits\RevisionableTrait;
use RZP\Models\Admin\Base;

class Entity extends Base\Entity
{
    use RevisionableTrait;

    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const CATEGORY          = 'category';
    const ASSIGNABLE        = 'assignable';

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
    ];

    protected $visible = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::CATEGORY,
        self::ASSIGNABLE,
    ];

    protected $casts = [
        self::ASSIGNABLE => 'bool',
    ];

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
     *
     * TODO : Check if required
     **/
    public function org()
    {
        return $this->morphedByMany('RZP\Models\Admin\Role\Entity', 'entity', Table::PERMISSION_MAP);
    }
}
