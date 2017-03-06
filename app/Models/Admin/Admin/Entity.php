<?php

namespace RZP\Models\Admin\Admin;

use Illuminate\Database\Eloquent\SoftDeletes;

use App;
use Hash;
use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;
use RZP\Models\Base\Traits\RevisionableTrait;

class Entity extends Base\Entity
{
    use SoftDeletes;
    // enable revisioning on this entity
    use RevisionableTrait;

    const ORG_ID                = 'org_id';
    const NAME                  = 'name';
    const USERNAME              = 'username';
    const EMAIL                 = 'email';
    const PASSWORD              = 'password';
    const PASSWORD_CONFIRMATION = 'password_confirmation';
    const REMEMBER_TOKEN        = 'remember_token';
    const OAUTH_ACCESS_TOKEN    = 'oauth_access_token';
    const OAUTH_PROVIDER_ID     = 'oauth_provider_id';
    const USER_TYPE             = 'user_type';
    const EMPLOYEE_CODE         = 'employee_code';
    const BRANCH_CODE           = 'branch_code';
    const DEPARTMENT_CODE       = 'department_code';
    const SUPERVISOR_CODE       = 'supervisor_code';
    const LOCATION_CODE         = 'location_code';
    const DISABLED              = 'disabled';
    const LOCKED                = 'locked';
    const LAST_LOGIN_AT         = 'last_login_at';
    const FAILED_ATTEMPTS       = 'failed_attempts';
    const OLD_PASSWORDS         = 'old_passwords';
    const OLD_PASSWORD          = 'old_password';
    const PASSWORD_EXPIRY       = 'password_expiry';
    const PASSWORD_CHANGED_AT   = 'password_changed_at';
    const EXPIRED_AT            = 'expired_at';
    const DELETED_AT            = 'deleted_at';
    const ROLES                 = 'roles';
    const MERCHANTS             = 'merchants';
    const GROUPS                = 'groups';
    const ALLOW_ALL_MERCHANTS   = 'allow_all_merchants';

    protected $dontKeepRevisionOf = [
        self::PASSWORD,
        self::PASSWORD_CONFIRMATION,
        self::REMEMBER_TOKEN,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::OLD_PASSWORDS
    ];

    protected $revisionEnabled = true;

    protected $revisionCreationsEnabled = true;

    protected static $sign = 'admin';

    protected $entity = 'admin';

    protected $generateIdOnCreate = false;

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
        self::LAST_LOGIN_AT,
        self::ALLOW_ALL_MERCHANTS,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::ORG_ID,
        self::NAME,
        self::USERNAME,
        self::EMAIL,
        self::REMEMBER_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::USER_TYPE,
        self::EMPLOYEE_CODE,
        self::BRANCH_CODE,
        self::DEPARTMENT_CODE,
        self::SUPERVISOR_CODE,
        self::LOCATION_CODE,
        self::DISABLED,
        self::LOCKED,
        self::LAST_LOGIN_AT,
        self::FAILED_ATTEMPTS,
        self::EXPIRED_AT,
        self::DELETED_AT,
        self::ALLOW_ALL_MERCHANTS,
        self::ROLES,
        self::GROUPS,
        self::MERCHANTS,
    ];

    protected $public = [
        self::ID,
        self::ENTITY,
        self::EMAIL,
        self::NAME,
        self::USERNAME,
        self::REMEMBER_TOKEN,
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
        self::DELETED_AT,
        self::LAST_LOGIN_AT,
        self::ALLOW_ALL_MERCHANTS,
        self::ROLES,
        self::GROUPS,
        self::MERCHANTS,
    ];

    protected $casts = [
        self::FAILED_ATTEMPTS     => 'int',
        self::ALLOW_ALL_MERCHANTS => 'bool',
        self::LOCKED              => 'bool',
        self::DISABLED            => 'bool',
    ];

    protected $publicSetters = [
        self::ID,
        self::ORG_ID,
    ];

    protected $defaults = [
        self::BRANCH_CODE         => 'default_branch',
        self::SUPERVISOR_CODE     => 'default_supervisor',
        self::DEPARTMENT_CODE     => 'default_location',
        self::LOCATION_CODE       => 'default_department',
        self::EMPLOYEE_CODE       => 'default_employee',
        self::ALLOW_ALL_MERCHANTS => false,
    ];

    protected static $unsetCreateInput = [
        self::PASSWORD_CONFIRMATION
    ];

    protected static $unsetEditInput = [
        self::PASSWORD_CONFIRMATION
    ];

    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($admin)
        {
            $admin->tokens()->delete();
        });
    }

    // -------------- Relations -------------
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
        return $this->morphToMany('RZP\Models\Admin\Group\Entity', 'entity', Table::GROUP_MAP);
    }

    public function merchants()
    {
        return $this->morphToMany('RZP\Models\Merchant\Entity', 'entity', Table::MERCHANT_MAP);
    }

    public function tokens()
    {
        return $this->hasMany('RZP\Models\Admin\Admin\Token\Entity');
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

    public function getDefaults()
    {
        return $this->defaults;
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
        $this->setAttribute(self::LAST_LOGIN_AT, Carbon::now()->timestamp);
    }

    public function getLastLoginAt()
    {
        return $this->getAttribute(self::LAST_LOGIN_AT);
    }

    public function getPasswordChangedAt()
    {
        return $this->getAttribute(self::PASSWORD_CHANGED_AT);
    }

    public function getFailedAttempts()
    {
        return $this->getAttribute(self::FAILED_ATTEMPTS);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getFirstName()
    {
        return explode(' ', $this->getName())[0];
    }

    public function getOAuthAccessToken()
    {
        return $this->getAttribute(self::OAUTH_ACCESS_TOKEN);
    }

    public function getOAuthProviderId()
    {
        return $this->getAttribute(self::OAUTH_PROVIDER_ID);
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

        $policy = $policy->toArray();

        $maxPasswordsToRetain = $policy[Org\AuthPolicy\Entity::MAX_PASSWORD_RETAIN];

        $oldPasswords = $this->getAttribute(self::OLD_PASSWORDS);

        $oldPasswordsCount = count($oldPasswords);

        if ($oldPasswordsCount >= $maxPasswordsToRetain)
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

        $this->setOldPasswords();

        $this->updatePasswordChangedAt();
    }

    protected function updatePasswordChangedAt()
    {
        $this->setAttribute(self::PASSWORD_CHANGED_AT, Carbon::now()->timestamp);
    }

    protected function setOldPasswordsAttribute($oldPasswords = [])
    {
        $this->attributes[self::OLD_PASSWORDS] = json_encode($oldPasswords);
    }

    /*
     * Setters
     *
     */
    public function setPassword(string $password)
    {
        $this->setAttribute(self::PASSWORD, $password);
    }

    public function setAllowAllMerchants()
    {
        $this->setAttribute(self::ALLOW_ALL_MERCHANTS, true);
    }

    /*
     * Getters
     *
     */
    public function getOldPasswords()
    {
        return $this->getAttribute(self::OLD_PASSWORDS);
    }

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function canSeeAllMerchants()
    {
        return $this->getAttribute(self::ALLOW_ALL_MERCHANTS);
    }

    /*
     * Accessors
     *
     */
    protected function getOldPasswordsAttribute()
    {
        $oldPasswords = null;

        if (isset($this->attributes[self::OLD_PASSWORDS]) === true)
        {
            $oldPasswords = $this->attributes[self::OLD_PASSWORDS];
        }

        if ($oldPasswords === null)
        {
            return [];
        }

        return json_decode($oldPasswords, true);
    }

    public function isSuperAdmin()
    {
        $roles = $this->roles;

        foreach ($roles as $role)
        {
            // default role is SuperAdmin
            if ($role->isSuperAdminRole() === true)
            {
                return true;
            }
        }

        return false;
    }

    public function isLocked()
    {
        return $this->getAttribute(self::LOCKED);
    }

    public function isDisabled()
    {
        return $this->getAttribute(self::DISABLED);
    }

    public function matchPassword(string $password)
    {
        $expectedPassword = $this->getPassword(self::PASSWORD);

        return Hash::check($password, $expectedPassword);
    }

    public function getPublicOrgId()
    {
        $orgId = $this->getAttribute(self::ORG_ID);

        return Org\Entity::getSignedId($orgId);
    }
}
