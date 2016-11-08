<?php

namespace RZP\Models\Admin\Group;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;

class Entity extends Base\PublicEntity
{
    const NAME             = 'name';
    const DESCRIPTION      = 'description';
    const ORG_ID           = 'org_id';

    protected $table = Table::GROUP;

    protected $entity = 'group';

    protected static $sign = 'grp';

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
        self::CREATED_AT,
    ];

    // Immediate higher groups which have access to this group and its
    // merchants
    public function parents()
    {
        return $this->morphedByMany('RZP\Models\Admin\Group\Entity', 'entity', Table::GROUP_MAP);
    }

    // Admins part of this group who have defined permissions
    // over the merchants in this group
    public function admins()
    {
        return $this->morphedByMany('RZP\Models\Admin\Admin\Entity', 'entity', Table::GROUP_MAP);
    }

    public function subGroups()
    {
        return $this->morphToMany('RZP\Models\Admin\Group\Entity', 'entity', Table::GROUP_MAP);
    }

    public function merchants()
    {
        return $this->morphToMany('RZP\Models\Merchant\Entity', 'entity', Table::MERCHANT_MAP);
    }

    public function roles()
    {
        return $this->morphToMany('RZP\Models\Admin\Role\Entity', 'entity', Table::ROLE_MAP);
    }
}
