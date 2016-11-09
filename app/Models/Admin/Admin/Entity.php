<?php

namespace RZP\Models\Admin\Admin;

use App;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;

class Entity extends Base\PublicEntity
{
    const NAME                      = 'name';
    const USERNAME                  = 'username';
    const PASSWORD                  = 'password';
    const EMAIL                     = 'email';
    const REMEMBER_TOKEN            = 'remember_token';
    const OAUTH_ACCESS_TOKEN        = 'access_token';
    const OAUTH_PROVIDER_ID         = 'oauth_provider_id';
    const ORG_ID                    = 'org_id';
    const USER_TYPE                 = 'user_type';
    const EMPLOYEE_CODE             = 'employee_code';
    const BRANCH_CODE               = 'branch_code';
    const DEPARTMENT_CODE           = 'department_code';
    const SUPERVISOR_CODE           = 'supervisor_code';
    const LOCATION_CODE             = 'location_code';
    const DISABLED                  = 'disabled';
    const LOCKED                    = 'locked';
    const LAST_LOGIN_AT             = 'last_login_at';
    const RECENT_PASSWORD           = 'recent_password';
    const PASSWORD_EXPIRY           = 'password_expiry';
    const EXPIRY_AT                 = 'expiry_at';
    const DELETED_AT                = 'deleted_at';

    protected static $sign = 'admin';

    protected $entity = 'admin';

    protected $table = Table::ADMIN;

    protected $generateIdOnCreate = true;

    protected $fillable = [
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::PASSWORD,
        self::REMEMBER_TOKEN,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::ORG_ID,
        self::USER_TYPE,
        self::EMPLOYEE_CODE,
        self::BRANCH_CODE,
        self::DEPARTMENT_CODE,
        self::SUPERVISOR_CODE,
        self::LOCATION_CODE,
        self::DISABLED,
        self::LOCKED,
        self::LAST_LOGIN_AT
    ];

    protected $visible = [
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::REMEMBER_TOKEN,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::ORG_ID,
        self::USER_TYPE,
        self::EMPLOYEE_CODE,
        self::BRANCH_CODE,
        self::DEPARTMENT_CODE,
        self::SUPERVISOR_CODE,
        self::LOCATION_CODE,
        self::DISABLED,
        self::LOCKED,
        self::LAST_LOGIN_AT
    ];

    protected $public = [
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::ORG_ID,
        self::USER_TYPE,
        self::EMPLOYEE_CODE,
        self::BRANCH_CODE,
        self::DEPARTMENT_CODE,
        self::SUPERVISOR_CODE,
        self::LOCATION_CODE,
        self::DISABLED,
        self::LOCKED
    ];

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function org()
    {
        return $this->belongsTo('Org\Entity');
    }

    public function roles()
    {
        return $this->morphToMany('RZP\Models\Admin\Role\Entity', 'entity', Table::ROLE_MAP);
    }

    // Admins can be part of multiple groups
    public function groups()
    {
        return $this->morphToMany('\RZP\Models\Admin\Group\Entity', 'entity', Table::GROUP_MAP);
    }

    public function merchants()
    {
        return $this->morphToMany('RZP\Models\Merchant\Entity', 'entity', Table::MERCHANT_MAP);
    }

    public function token()
    {
        return $this->hasMany('Token\Entity');
    }

    public function getPermissionsList()
    {
        $permissions = [];

        // Create a list of all the permissions from all the roles
        foreach ($this->roles as $role)
        {
            foreach ($role->permissions->toArray() as $permission)
            {
                $permissions[] = $permission['name'];
            }
        }

        $permissions = array_unique($permissions);

        return $permissions;
    }

    public function saveOrFailMerchant(Merchant\Entity $merchant)
    {
        // TODO: Restrict No of merchants per admin to 1.
        $this->merchants()->save($merchant);

        return $merchant;
    }
}
