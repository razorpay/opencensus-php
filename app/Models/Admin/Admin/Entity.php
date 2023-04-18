<?php

namespace RZP\Models\Admin\Admin;

use Illuminate\Database\Eloquent\SoftDeletes;

use App;
use Hash;
use Carbon\Carbon;
use RZP\Constants\Table;
use RZP\Exception;
use RZP\Error\ErrorCode;
use RZP\Models\Base\Traits\RevisionableTrait;

use RZP\Models\Merchant;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Permission;
use RZP\Models\Admin\Role;
use RZP\Models\Admin\Group;
use RZP\Models\Workflow\Action;

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
    const PASSWORD_RESET_TOKEN  = 'password_reset_token';
    const PASSWORD_RESET_EXPIRY = 'password_reset_expiry';
    const EXPIRED_AT            = 'expired_at';
    const DELETED_AT            = 'deleted_at';
    const ROLES                 = 'roles';
    const MERCHANTS             = 'merchants';
    const GROUPS                = 'groups';
    const ALLOW_ALL_MERCHANTS   = 'allow_all_merchants';
    const WRONG_2FA_ATTEMPTS            = 'wrong_2fa_attempts';
    //added for org level enforcing of 2fa
    const ORG_ENFORCED_SECOND_FACTOR_AUTH = 'org_enforced_second_factor_auth';

    const NEXT_PASSWORD_EXPIRY_DATE = 'next_password_expiry_date';
    const PASSWORD_EXPIRY_DAYS = '+30 days';


    protected $dontKeepRevisionOf = [
        self::PASSWORD,
        self::PASSWORD_CONFIRMATION,
        self::REMEMBER_TOKEN,
        self::OAUTH_ACCESS_TOKEN,
        self::OAUTH_PROVIDER_ID,
        self::OLD_PASSWORDS
    ];

    protected $embeddedRelations = [
        self::ROLES,
        self::GROUPS,
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
        self::PASSWORD_CONFIRMATION,
        self::REMEMBER_TOKEN,
        self::OAUTH_ACCESS_TOKEN,
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
        self::ALLOW_ALL_MERCHANTS,
        self::ORG_ENFORCED_SECOND_FACTOR_AUTH,
        self::WRONG_2FA_ATTEMPTS,
    ];

    protected $visible = [
        self::ID,
        self::ENTITY,
        self::ORG_ID,
        self::NAME,
        self::USERNAME,
        self::EMAIL,
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
        self::NEXT_PASSWORD_EXPIRY_DATE
    ];

    protected $appends = [
        self::NEXT_PASSWORD_EXPIRY_DATE
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
        self::ORG_ENFORCED_SECOND_FACTOR_AUTH,
        self::NEXT_PASSWORD_EXPIRY_DATE
    ];

    protected $hidden = [
        self::REMEMBER_TOKEN
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

    // Append expiry date to model
    public function getNextPasswordExpiryDateAttribute()
    {
        $passwordChangedAt = $this->getPasswordChangedAt();
        if (empty($passwordChangedAt))
        {
            return strtotime(self::PASSWORD_EXPIRY_DAYS,$this->getCreatedAtAttribute());
        }
        return strtotime(self::PASSWORD_EXPIRY_DAYS,$passwordChangedAt);
    }

    // -------------- Relations -------------
    public function org()
    {
        return $this->belongsTo(Org\Entity::class);
    }

    public function roles()
    {
        return $this->morphToMany(Role\Entity::class, 'entity', Table::ROLE_MAP);
    }

    // Admins can be part of multiple groups
    public function groups()
    {
        return $this->morphToMany(Group\Entity::class, 'entity', Table::GROUP_MAP);
    }

    public function merchants()
    {
        return $this->morphToMany(Merchant\Entity::class, 'entity', Table::MERCHANT_MAP);
    }

    public function tokens()
    {
        return $this->hasMany(Token\Entity::class);
    }

    public function workflows()
    {
        return $this->morphMany(Action\Entity::class, Action\Entity::MAKER);
    }

    public function getPermissionsList()
    {
        $permissions = [];

        $roles = $this->roles()->with('permissions')->get();

        // Create a list of all the permissions from all the roles
        foreach ($roles as $role)
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
        $this->setAttribute(self::LAST_LOGIN_AT, Carbon::now()->getTimestamp());
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

    public function getUsername() : string
    {
        return $this->getAttribute(self::USERNAME);
    }

    public function getFirstName()
    {
        return explode(' ', $this->getName())[0];
    }

    public function getOrgId()
    {
        return $this->getAttribute(self::ORG_ID);
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

    public function setPasswordResetToken(string $token = null)
    {
        $this->setAttribute(self::PASSWORD_RESET_TOKEN, $token);
    }

    public function setPasswordResetExpiry(int $expiry)
    {
        $this->setAttribute(self::PASSWORD_RESET_EXPIRY, $expiry);
    }

    public function getPasswordResetToken()
    {
        return $this->getAttribute(self::PASSWORD_RESET_TOKEN);
    }

    public function getPasswordResetExpiry()
    {
        return $this->getAttribute(self::PASSWORD_RESET_EXPIRY);
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

    // Mutated Attribute function for setPassword Method
    public function setPasswordAttribute($password)
    {
        $this->attributes[self::PASSWORD] = Hash::make($password);

        $this->setOldPasswords();

        $this->updatePasswordChangedAt();
    }

    protected function setLockedAttribute($locked)
    {
        $this->attributes[self::LOCKED] = $locked;

        if (empty($locked) === true)
        {
            $this->setAttribute(self::FAILED_ATTEMPTS, 0);
        }
    }

    protected function updatePasswordChangedAt()
    {
        $this->setAttribute(self::PASSWORD_CHANGED_AT, Carbon::now()->getTimestamp());
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
    public function setWrong2faAttempts(int $wrongAttempts)
    {
        $this->setAttribute(self::WRONG_2FA_ATTEMPTS, $wrongAttempts);
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

    public function getEmail() : string
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function canSeeAllMerchants()
    {
        return $this->getAttribute(self::ALLOW_ALL_MERCHANTS);
    }
    public function getWrong2faAttempts(): int
    {
        return ($this->getAttribute(self::WRONG_2FA_ATTEMPTS));
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

    public function getSuperAdminRole()
    {
        $roles = $this->roles;

        foreach ($roles as $role)
        {
            // default role is SuperAdmin
            if ($role->isSuperAdminRole() === true)
            {
                return $role;
            }
        }

        return null;
    }

    /**
     * @return mixed|null
     *
     * Returns the first role which has the reject_payout_bulk
     * permission. This is used when an admin is trying to reject
     * a payout, to fill state_changer_role_id in action_checker table.
     */
    public function getPayoutRejectRole()
    {
        $roles = $this->roles()->with(Entity::PERMISSIONS)->get();

        foreach ($roles as $role)
        {
            $permissionNamesArray = array_column($role->permissions->toArray(), Entity::NAME);

            if (in_array(Permission\Name::REJECT_PAYOUT_BULK, $permissionNamesArray))
            {
                return $role;
            }
        }

        return null;
    }

    public function isLocked()
    {
        return $this->getAttribute(self::LOCKED);
    }

    public function isDisabled()
    {
        return $this->getAttribute(self::DISABLED);
    }

    public function isOrgEnforcedSecondFactorAuth(): bool
    {
        return ($this->getAttribute(self::ORG_ENFORCED_SECOND_FACTOR_AUTH) === true);
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
    protected function getOrgEnforcedSecondFactorAuthAttribute(): bool
    {
        $orgId = $this->getOrgId();

        return (new Org\Repository)
            ->hasAnyOrgEnforced2FaForAdmin($orgId);
    }

    public function getInputFields() : array
    {
        $extra = [
            self::ROLES,
            self::GROUPS,
        ];

        $app = App::getFacadeRoot();

        $orgId = $app['basicauth']->getAdminOrgId();

        $org = (new Org\Repository)->findOrFailPublic($orgId);

        if ($org->getAuthType() === Org\AuthType::PASSWORD)
        {
            $extra = array_merge(
                $extra,
                [self::PASSWORD, self::PASSWORD_CONFIRMATION]);
        }

        return array_merge($this->fillable, $extra);
    }

    /**
     * Get all relations to the array
     */
    public function getRelationsForDiffer() : array
    {
        return [
            self::ROLES,
            self::GROUPS,
        ];
    }

    public function hasPermission($permission)
    {
        $app = App::getFacadeRoot();

        if ($app['api.route']->isWorkflowExecuteOrApproveCall() === true)
        {
            return true;
        }

        $adminPermissions = $this->getPermissionsList();

        return (in_array($permission, $adminPermissions, true) === true);
    }

    public function hasPermissionOrFail($permission)
    {
        $hasPermission = $this->hasPermission($permission);

        if ($hasPermission === false)
        {
            throw new Exception\BadRequestException(
                    ErrorCode::BAD_REQUEST_ACCESS_DENIED);
        }

        return $hasPermission;
    }

    public function hasMerchantActionPermissionOrFail($action)
    {
        $routePermission = Permission\Name::$actionMap[$action];

        return $this->hasPermissionOrFail($routePermission);
    }

    public function getRolesAndPermissionsList(): array
    {
        $permissions = [];
        $roleNames = [];

        $roles = $this->roles()->with('permissions')->get();

        // Create a list of all the permissions from all the roles
        foreach ($roles as $role) {
            $roleNames[] = $role['name'];
            foreach ($role->permissions->toArray() as $permission) {
                $permissions[] = $permission['name'];
            }
        }

        $permissions = array_unique($permissions);
        $roleNames = array_unique($roleNames);

        return ['roles' => $roleNames, 'permissions' => $permissions];
    }
}
