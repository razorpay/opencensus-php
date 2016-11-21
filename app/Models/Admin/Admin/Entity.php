<?php

namespace RZP\Models\Admin\Admin;

use Illuminate\Database\Eloquent\SoftDeletes;

use App;
use Hash;
use RZP\Models\Base;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const NAME                      = 'name';
    const USERNAME                  = 'username';
    const PASSWORD                  = 'password';
    const EMAIL                     = 'email';
    const REMEMBER_TOKEN            = 'remember_token';
    const OAUTH_ACCESS_TOKEN        = 'oauth_access_token';
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
    const FAILED_ATTEMPTS           = 'failed_attempts';
    const OLD_PASSWORDS             = 'old_passwords';
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

    protected $public = [
        self::ID,
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::REMEMBER_TOKEN,
        self::ORG_ID,
        self::USER_TYPE,
        self::EMPLOYEE_CODE,
        self::BRANCH_CODE,
        self::DEPARTMENT_CODE,
        self::SUPERVISOR_CODE,
        self::LOCATION_CODE,
        self::DISABLED,
        self::LOCKED,
        self::DELETED_AT,
        self::LAST_LOGIN_AT,
        'roles',
        'groups',
        'merchants',
    ];

    protected $casts = [
        self::FAILED_ATTEMPTS => 'int',
    ];

    protected $publicSetters = array(
        self::ID,
        self::ORG_ID,
    );

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function org()
    {
        return $this->belongsTo('RZP\Models\Admin\Org\Entity');
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

    public function setPublicOrgIdAttribute(array & $array)
    {
        $array[self::ORG_ID] = Org\Entity::getSignedId($this->getAttribute(self::ORG_ID));
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

    public function isDeleted()
    {
        return ($this->getAttribute(self::DELETED_AT) !== null);
    }

    public function saveOrFailMerchant(Merchant\Entity $merchant)
    {
        // TODO: Restrict No of merchants per admin to 1.
        $this->merchants()->save($merchant);

        return $merchant;
    }

    public function lock()
    {
        $this->setAttribute(self::LOCKED, true);
    }

    public function unlock()
    {
        $this->setAttribute(self::FAILED_ATTEMPTS, 0);
        $this->setAttribute(self::LOCKED, false);
    }

    public function disable()
    {
        $this->setAttribute(self::DISABLED, true);
    }

    public function enable()
    {
        $this->setAttribute(self::DISABLED, false);
    }

    public function isInitialLogin()
    {
        return ($this->getLastLoginAt() === null);
    }

    public function updateLastLoginAt()
    {
        $this->setAttribute(self::LAST_LOGIN_AT, time());
    }

    public function getLastLoginAt()
    {
        return $this->getAttribute(self::LAST_LOGIN_AT);
    }

    public function getPasswordExpiry()
    {
        return $this->getAttribute(self::PASSWORD_EXPIRY);
    }

    public function getFailedAttempts()
    {
        return (int) $this->getAttribute(self::FAILED_ATTEMPTS);
    }

    public function incrementFailedAttempts()
    {
        $attempts = $this->getAttribute(self::FAILED_ATTEMPTS) + 1;

        $this->setFailedAttempts($attempts);
    }

    public function resetFailedAttempts()
    {
        $this->setFailedAttempts(0);
    }

    public function setFailedAttempts($attempts)
    {
        $this->setAttribute(self::FAILED_ATTEMPTS, $attempts);
    }

    public function setOldPasswords()
    {
        // $policy = $this->org->policy;
        $policy = new Org\AuthPolicy\Entity;

        $maxPasswordsToRetain = $policy->getMaxPasswordToRetain();

        $oldPasswords = $this->getAttribute(self::OLD_PASSWORDS);

        $oldPasswordsCount = count($oldPasswords);

        if ($oldPasswordsCount === $maxPasswordsToRetain)
        {
            array_shift($oldPasswords);
        }

        $oldPasswords[] = $this->getAttribute(self::PASSWORD);

        $this->setAttribute(self::OLD_PASSWORDS, $oldPasswords);
    }

    /*
     * Mutators
     *
     */
    public function setEmailAttribute(string $email)
    {
        $this->attributes[self::EMAIL] = strtolower($email);
    }

    protected function setPasswordAttribute($password)
    {
        $this->attributes[self::PASSWORD] = Hash::make($password);
    }

    protected function setOldPasswordsAttribute($oldPasswords = [])
    {
        $this->attributes[self::OLD_PASSWORDS] = json_encode($oldPasswords);
    }

    public function getOldPasswords()
    {
        return $this->getAttribute(self::OLD_PASSWORDS);
    }

    protected function getOldPasswordsAttribute()
    {
        if (isset($this->attributes[self::OLD_PASSWORDS]) === true)
        {
            $oldPasswords = $this->attributes[self::OLD_PASSWORDS];
        }
        else
        {
            $oldPasswords = null;
        }

        if ($oldPasswords === null)
        {
            return [];
        }

        return json_decode($oldPasswords, true);
    }

    public function toArrayPublic()
    {
         $admin = parent::toArrayPublic();

         $roles = $this->roles;

         $admin['roles'] = $roles->toArrayPublic()['items'];

         $merchants = $this->merchants;

         $admin['merchants'] = $merchants->toArrayPublic()['items'];

         $groups = $this->groups;

         $admin['groups'] = $groups->toArrayPublic()['items'];

         return $admin;
    }
}
