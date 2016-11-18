<?php

namespace RZP\Models\Admin\Group;

use illuminate\database\eloquent\softdeletes;

use App;
use RZP\Constants\Table;
use RZP\Models\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Role;
use RZP\Models\Merchant;



class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const NAME             = 'name';
    const DESCRIPTION      = 'description';
    const ORG_ID           = 'org_id';

    const DELETED_AT       = 'deleted_at';

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
        'admins',
        'roles',
        'merchants',
        'sub_groups',
    ];

    // Immediate higher groups which have access to this group and its
    // merchants
    public function parents()
    {
        return $this->morphedByMany('RZP\Models\Admin\Group\Entity', 'entity', Table::GROUP_MAP);
    }

    public function isDeleted()
    {
        return ($this->getAttribute(self::DELETED_AT) !== null);
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

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
    }

    public function toArrayPublic()
    {
        $group = parent::toArrayPublic();

        if (isset($group['admins']) === true)
        {
            foreach ($group['admins'] as $key => $entity)
            {
                $group['admins'][$key]['id'] = Admin\Entity::getSignedId($entity['id']);
            }
        }

        if (isset($group['merchants']) === true)
        {
            foreach ($group['merchants'] as $key => $entity)
            {
                $group['merchants'][$key]['id'] = Merchant\Entity::getSignedId($entity['id']);
            }
        }

        if (isset($group['roles']) === true)
        {
            foreach ($group['roles'] as $key => $entity)
            {
                $group['roles'][$key]['id'] = Role\Entity::getSignedId($entity['id']);
            }
        }

        if (isset($group['sub_groups']) === true)
        {
            foreach ($group['sub_groups'] as $key => $entity)
            {
                $group['sub_groups'][$key]['id'] = Entity::getSignedId($entity['id']);
            }
        }

        return $group;
    }
}
