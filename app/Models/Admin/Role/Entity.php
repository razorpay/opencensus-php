<?php

namespace RZP\Models\Admin\Role;

use App;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\User;
use RZP\Constants\Table;
use RZP\Constants\Product;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Org\Entity as Org;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\Entity
{
    use SoftDeletes;
    use RevisionableTrait;

    const NAME              = 'name';
    const DESCRIPTION       = 'description';
    const ORG_ID            = 'org_id';
    const PRODUCT           = 'product';
    const DELETED_AT        = 'deleted_at';

    /**
     * Holds all the permissions as relation key.
     */
    const PERMISSIONS       = 'permissions';
    const ADMINS            = 'admins';
    const ROLES             = 'roles';

    protected $entity = 'role';

    protected static $sign = 'role';

    protected $generateIdOnCreate = false;

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected $embeddedRelations = [
        self::PERMISSIONS,
    ];

    protected $fillable = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::PRODUCT,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::DESCRIPTION,
        self::ORG_ID,
        self::PERMISSIONS,
        self::ADMINS
    ];

    protected $diff = [
        self::NAME,
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

    protected $defaults = [
        self::PRODUCT => Product::PRIMARY,
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

    public function users()
    {
        return $this->morphedByMany(User\Entity::class, 'entity', Table::ROLE_MAP);
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
    public function getOrgId() : string
    {
        return $this->getAttribute(self::ORG_ID);
    }

    public function getName() : string
    {
        return $this->getAttribute(self::NAME);
    }

    public function isSuperAdminRole()
    {
        // Default role is SuperAdmin
        if (strtolower(config('heimdall.default_role_name')) === strtolower($this->getName()))
        {
            return true;
        }

        return false;
    }

    public function getRelationsForDiffer() : array
    {
        return [
            self::PERMISSIONS,
        ];
    }

    /**
     * @param        $query
     * @param string $product
     */
    public function scopeProduct(Builder $query, string $product)
    {
        $query->where(self::PRODUCT, '=', $product);
    }
}
