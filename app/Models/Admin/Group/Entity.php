<?php

namespace RZP\Models\Admin\Group;

use Illuminate\Database\Eloquent\SoftDeletes;

use App;
use RZP\Models\Base\Traits\RevisionableTrait;
use RZP\Constants\Table;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Admin\Role;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Constants\Environment;

class Entity extends Base\Entity
{
    use SoftDeletes;
    use RevisionableTrait;

    const NAME             = 'name';
    const DESCRIPTION      = 'description';
    const ORG_ID           = 'org_id';

    const PARENTS          = 'parents';
    const SUB_GROUPS       = 'sub_groups';

    protected $entity = 'group';

    protected static $sign = 'grp';

    protected $generateIdOnCreate = false;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
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
        self::SUB_GROUPS,
        self::PARENTS,
    ];

    protected $diff = [
        self::NAME,
        self::DESCRIPTION,
    ];

    protected $publicSetters = [
        self::ID,
        self::ORG_ID,
    ];

    protected $embeddedRelations = [
        self::SUB_GROUPS,
        self::PARENTS,
    ];

    // Immediate higher groups which have access to this
    // group and its merchants
    public function parents()
    {
        // Since we're doing morphToMany, it'll mean that all the entity IDs
        // on the **left** in GROUP_MAP will be the parents.
        //
        // SQL = WHERE entity_id = calling_entity_id AND entity_type = 'group'
        return $this->morphToMany('RZP\Models\Admin\Group\Entity', 'entity', Table::GROUP_MAP);
    }

    public function subGroups()
    {
        // Since we're doing morphedByMany, it'll mean all the entity IDs
        // on the **right** in GROUP_MAP are the children
        //
        // SQL = WHERE group_id = calling_entity_id AND entity_type = 'group'
        return $this->morphedByMany('RZP\Models\Admin\Group\Entity', 'entity', Table::GROUP_MAP);
    }

    // Admins part of this group who have defined permissions
    // over the merchants in this group
    public function admins()
    {
        return $this->morphedByMany('RZP\Models\Admin\Admin\Entity', 'entity', Table::GROUP_MAP);
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

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    /**
     *
     * @return array
     */
    public function getSalesForceGroupId()
    {
        return [
            Constant::SALESFORCE_CLAIMED_MERCHANTS_GROUP_ID => Constant::SF_CLAIMED_MERCHANTS_GROUP_ID,
            Constant::SALESFORCE_CLAIMED_SME_GROUP_ID       => Constant::SF_CLAIMED_SME_GROUP_ID,
            Constant::SALESFORCE_UNCLAIMED_GROUP_ID         => Constant::SF_UNCLAIMED_GROUP_ID
        ];
    }
}
