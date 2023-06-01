<?php

namespace RZP\Models\User;
use App;
use Hash;
use RZP\Models\Base;
use RZP\Models\Admin;
use RZP\Models\Feature;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Constants\Table;
use RZP\Models\Admin\Org;
use RZP\Models\Admin\Role;
use RZP\Models\Invitation;
use RZP\Models\Merchant\MerchantUser;
use RZP\Models\Merchant\RazorxTreatment;
use RZP\Models\Feature\Constants as Features;
use RZP\Models\Merchant\Balance\Type as ProductType;
use RZP\Services\Dcs\Features\Constants as DcsConstants;
use RZP\Services\Dcs\Features\Service as DCSService;
use RZP\Services\Dcs\Features\Type;
use RZP\Trace\TraceCode;

class Entity extends Base\PublicEntity
{
    const ID_LENGTH = 14;

    const ID                            = 'id';
    const NAME                          = 'name';
    const EMAIL                         = 'email';
    const PASSWORD                      = 'password';
    const OLD_PASSWORD                  = 'old_password';
    const AUDIT_ID                      = 'audit_id';

    // !! DEPRECATED; replaced with old_passwords attribute below.
    // The attribute OLD_PASSWORD_1 and OLD_PASSWORD_2 are stored in table which
    // gets used during password reset to assert new password doest not match
    // last three passwords. Ref User/Validator.php file.
    const OLD_PASSWORD_1                = 'old_password1';
    const OLD_PASSWORD_2                = 'old_password2';

    const OLD_PASSWORDS                 = 'old_passwords';

    const PASSWORD_CONFIRMATION         = 'password_confirmation';
    const CONTACT_MOBILE                = 'contact_mobile';
    const PHONE                         = 'Phone';
    const REMEMBER_TOKEN                = 'remember_token';
    const CONFIRM_TOKEN                 = 'confirm_token';
    const PASSWORD_RESET_TOKEN          = 'password_reset_token';
    const PASSWORD_RESET_EXPIRY         = 'password_reset_expiry';
    const SECOND_FACTOR_AUTH            = 'second_factor_auth';
    const SECOND_FACTOR_AUTH_ENFORCED   = 'second_factor_auth_enforced';
    const SECOND_FACTOR_AUTH_SETUP      = 'second_factor_auth_setup';
    const WRONG_2FA_ATTEMPTS            = 'wrong_2fa_attempts';
    const RESTRICTED                    = 'restricted';
    const ACCOUNT_LOCKED                = 'account_locked';
    const CAPTCHA                       = 'captcha';
    const CAPTCHA_DISABLE               = 'captcha_disable';
    const SKIP_CAPTCHA_VALIDATION       = 'skip_captcha_validation';

    //added for org level enforcing of 2fa
    const ORG_ENFORCED_SECOND_FACTOR_AUTH = 'org_enforced_second_factor_auth';

    const SIGNUP_VIA_EMAIL              = 'signup_via_email';
    const TOKEN                         = 'token';
    const EXPIRY_TIME                   = 'expiryTime';
    const COMPANY                       = 'company';
    const REVENUE                       = 'revenue';

    // This key in request body checks if the request to register user or resend verification link
    // came from new signup flow for X.
    // Remove this when signup experiment for X is ramped up.
    const X_VERIFY_EMAIL                = 'x_verify_email';

    // This token is used for user authorization between api calls
    const OTP_AUTH_TOKEN                = 'otp_auth_token';

    const ACTION                        = 'action';
    const USER_ID                       = 'user_id';
    const MERCHANT_ID                   = 'merchant_id';
    const MERCHANTS                     = 'merchants';
    const ROLE                          = 'role';
    const ROLE_NAME                     = 'role_name';
    const BANKING_ROLE                  = 'banking_role';
    const BANKING_ROLE_NAME             = 'banking_role_name';
    const PIVOT                         = 'pivot';
    const OWNER                         = 'owner';
    const CONFIRMED                     = 'confirmed';
    const INVITATIONS                   = 'invitations';
    const PRODUCT                       = 'product';

    const ACTOR_INFO                    = 'actor_info';

    const APP                           = 'app';

    const OAUTH_PROVIDER                = 'oauth_provider';

    const PASSWORD_TOKEN_LENGTH         = 50;

    // Boolean attribute is true if contact mobile is verified via OTP
    const CONTACT_MOBILE_VERIFIED       = 'contact_mobile_verified';
    // Boolean attribute is true if email is verified; this is equivalent to `confirmed` attribute
    // which should be deprecated for sake of brevity
    const EMAIL_VERIFIED       = 'email_verified';

    // Additional input keys
    const MEDIUM                        = 'medium';
    const OTP                           = 'otp';
    const MEDIUM_SMS                    = 'sms';
    const MEDIUM_EMAIL                  = 'email';
    const SETTINGS                      = 'settings';

    // Settings keys
    const SETTINGS_SKIP_CONTACT_MOBILE_VERIFY = 'skip_contact_mobile_verify';

    protected $entity = 'user';

    const VERIFICATION_TYPE = 'verification_type';
    const LINK              = 'link';

    // cookie name clientId is taken as visitor Id.
    const CLIENT_ID  = 'clientId';
    const VISITOR_ID = 'visitorId';

    // Constant for skipping sms verification on stage

    const SKIP_SMS_REQUEST    = 'skip_sms_request';

    protected $fillable = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::PASSWORD,
        self::CONTACT_MOBILE,
        self::REMEMBER_TOKEN,
        self::CONFIRM_TOKEN,
        self::OAUTH_PROVIDER,
        self::PASSWORD_RESET_TOKEN,
        self::PASSWORD_RESET_EXPIRY,
        self::ORG_ENFORCED_SECOND_FACTOR_AUTH,
        self::SIGNUP_VIA_EMAIL,
        self::AUDIT_ID
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::CONTACT_MOBILE,
        self::CONTACT_MOBILE_VERIFIED,
        self::EMAIL_VERIFIED,
        self::SECOND_FACTOR_AUTH,
        self::SECOND_FACTOR_AUTH_ENFORCED,
        self::SECOND_FACTOR_AUTH_SETUP,
        self::ORG_ENFORCED_SECOND_FACTOR_AUTH,
        self::RESTRICTED,
        self::CONFIRMED,
        self::ACCOUNT_LOCKED,
        self::CREATED_AT,
        self::SIGNUP_VIA_EMAIL,
    ];

    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN,
        self::CONFIRM_TOKEN,
        self::PASSWORD_RESET_TOKEN,
        self::OLD_PASSWORD_1,
        self::OLD_PASSWORD_2,
        self::OLD_PASSWORDS,
        self::OAUTH_PROVIDER,
    ];

    protected static $generators = [
        self::ID,
        self::CONFIRM_TOKEN,
    ];

    protected static $modifiers = [
        self::EMAIL,
    ];

    protected $defaults = [
        self::CONTACT_MOBILE_VERIFIED => 0,
    ];

    protected $casts = [
        self::CONTACT_MOBILE_VERIFIED       => 'bool',
        self::SECOND_FACTOR_AUTH            => 'bool',
        self::ACCOUNT_LOCKED                => 'bool',
        self::OLD_PASSWORDS                 => 'array'
    ];

    protected $generateIdOnCreate = true;

    protected $appends = [
        self::CONFIRMED,
        self::EMAIL_VERIFIED,
        self::SECOND_FACTOR_AUTH_ENFORCED,
        self::SECOND_FACTOR_AUTH_SETUP,
        self::RESTRICTED,
        self::ORG_ENFORCED_SECOND_FACTOR_AUTH,
    ];

    // --------------------- Modifiers ---------------------------------------------

    /**
     * Modifies the email to have lower.
     * @param $input
     */
    protected function modifyEmail(& $input)
    {
        if (empty($input[self::EMAIL]) === false)
        {
            $input[self::EMAIL] = mb_strtolower($input[self::EMAIL]);
        }
    }

    // --------------------- Modifiers Ends ----------------------------------------

    public function build(array $input = [], string $operation = 'create')
    {
        $this->input = $input;

        $this->modify($input);

        $this->validateInput($operation, $input);

        $this->generate($input);

        $this->unsetInput($operation, $input);

        $this->fill($input);

        return $this;
    }

    /**
     * Generates a one time use token of the given length
     */
    protected function generateOneTimeUseToken($length)
    {
        $bytes = random_bytes($length / 2);
        $token = bin2hex($bytes);

        return $token;
    }

    /**
     * Generates confirmation token
     */
    protected function generateConfirmToken()
    {
        $this->setAttribute(self::CONFIRM_TOKEN, $this->generateOneTimeUseToken(32));
    }

    /**
     * Order by owned first. In case of multiple owned merchants with same
     * email, pick first. Followed by owned merchants with different emails.
     */
    public function merchants()
    {
        $sql = "CASE WHEN email=? AND role='owner' THEN 0
                     WHEN role='owner' THEN 1
                     else 2 END";

        return $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                    ->withPivot([self::ROLE, self::PRODUCT])
                    ->orderByRaw($sql, [$this->getEmail()]);
    }

    public function merchantsForOrg(string $orgId)
    {
        $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $sql = "CASE WHEN email=? AND role='owner' THEN 0
                     WHEN role='owner' THEN 1
                     else 2 END";

        return $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
            ->where(Merchant\Entity::ORG_ID, $orgId)
            ->withPivot([self::ROLE, self::PRODUCT])
            ->orderByRaw($sql, [$this->getEmail()]);
    }

    public function primaryMerchants()
    {
        return $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                    ->withPivot([self::ROLE, self::PRODUCT])
                    ->wherePivot(self::PRODUCT, 'primary');
    }

    public function bankingMerchants()
    {
        return $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                    ->withPivot([self::ROLE, self::PRODUCT])
                    ->wherePivot(self::PRODUCT, 'banking');
    }

    public function merchantsByProductAndRole($product = ProductType::PRIMARY, $role = \RZP\Models\User\Role::OWNER)
    {
        return $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
            ->withPivot([self::ROLE, self::PRODUCT])
            ->wherePivot(self::PRODUCT, $product)
            ->wherePivot(self::ROLE, $role);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation\Entity::class, Invitation\Entity::EMAIL, Entity::EMAIL)
                    ->orderBy(Invitation\Entity::CREATED_AT, 'desc');
    }

    public function roles()
    {
        return $this->morphToMany(Role\Entity::class, 'entity', Table::ROLE_MAP);
    }

    public function setConfirmTokenNull()
    {
        $this->setAttribute(self::CONFIRM_TOKEN, null);
    }

    public function setPasswordResetToken(string $token = null)
    {
        $this->setAttribute(self::PASSWORD_RESET_TOKEN, $token);
    }

    public function setOauthProvider(string $oauthProvider = null)
    {
        $this->setAttribute(self::OAUTH_PROVIDER, $oauthProvider);
    }

    public function setPasswordResetExpiry(int $expiry)
    {
        $this->setAttribute(self::PASSWORD_RESET_EXPIRY, $expiry);
    }

    public function setPasswordNull()
    {
        $this->attributes[self::PASSWORD] = null;
    }

    public function getOauthProvider()
    {
        return $this->getAttribute(self::OAUTH_PROVIDER);
    }

    public function getPasswordResetToken()
    {
        return $this->getAttribute(self::PASSWORD_RESET_TOKEN);
    }

    public function getPasswordResetExpiry()
    {
        return $this->getAttribute(self::PASSWORD_RESET_EXPIRY);
    }

    protected function setPasswordAttribute($password)
    {
        $this->attributes[self::PASSWORD] = Hash::make($password);
    }

    public function getContactMobile()
    {
        return $this->getAttribute(self::CONTACT_MOBILE);
    }

    public function isSignupViaEmail()
    {
        return $this->getAttribute(self::SIGNUP_VIA_EMAIL) === 1;
    }

    public function setContactMobile($contact)
    {
        $this->setAttribute(self::CONTACT_MOBILE, $contact);
    }

    public function getName()
    {
        return $this->getAttribute(self::NAME);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function setEmail(string $email)
    {
        $formattedEmail = ($email === null) ? null : mb_strtolower($email);
        $this->setAttribute(self::EMAIL, $formattedEmail);
    }

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function getConfirmToken()
    {
        return $this->getAttribute(self::CONFIRM_TOKEN);
    }

    public function getConfirmedAttribute()
    {
        return ($this->getAttribute(self::CONFIRM_TOKEN) === null);
    }

    public function getEmailVerifiedAttribute()
    {
        return ($this->getAttribute(self::CONFIRM_TOKEN) === null);
    }

    public function isContactMobileVerified(): bool
    {
        return ($this->getAttribute(self::CONTACT_MOBILE_VERIFIED) === true);
    }

    public function setContactMobileVerified(bool $verified)
    {
        $this->setAttribute(self::CONTACT_MOBILE_VERIFIED, $verified);
    }

    public function isSecondFactorAuth(): bool
    {
        return ($this->getAttribute(self::SECOND_FACTOR_AUTH) === true);
    }

    public function setSecondFactorAuth(bool $enabled)
    {
        $this->setAttribute(self::SECOND_FACTOR_AUTH, $enabled);
    }

    public function getWrong2faAttempts(): int
    {
        return ($this->getAttribute(self::WRONG_2FA_ATTEMPTS));
    }

    public function setWrong2faAttempts(int $wrongAttempts)
    {
        $this->setAttribute(self::WRONG_2FA_ATTEMPTS, $wrongAttempts);
    }

    public function isAccountLocked(): bool
    {
        return ($this->getAttribute(self::ACCOUNT_LOCKED) === true);
    }

    public function setAccountLocked(bool $locked)
    {
        $this->setAttribute(self::ACCOUNT_LOCKED, $locked);
    }

    public function isSecondFactorAuthEnforced(): bool
    {
        return ($this->getAttribute(self::SECOND_FACTOR_AUTH_ENFORCED) === true);
    }

    public function getRestricted(): bool
    {
        return ($this->getAttribute(self::RESTRICTED) === true);
    }

    public function isOrgEnforcedSecondFactorAuth(): bool
    {
        return ($this->getAttribute(self::ORG_ENFORCED_SECOND_FACTOR_AUTH) === true);
    }

    public function isSecondFactorAuthSetup(): bool
    {
        return ($this->getAttribute(self::SECOND_FACTOR_AUTH_SETUP) === true);
    }

    public function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::USER);
    }

    protected function getSecondFactorAuthEnforcedAttribute(): bool
    {
        return ($this->getOrgEnforcedSecondFactorAuthAttribute() === true) or
            ($this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                ->where(Merchant\Entity::SECOND_FACTOR_AUTH, '=', true)
                ->limit(1)
                ->count() > 0);
    }

    protected function getSecondFactorAuthSetupAttribute(): bool
    {
        return (($this->getContactMobile() !== null) and
                ($this->isContactMobileVerified() === true));
    }

    protected function getOrgEnforcedSecondFactorAuthAttribute(): bool
    {
        $merchants = $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                          ->select(Table::MERCHANT . '.' . Merchant\Entity::ORG_ID)
                          ->get();

        $orgIdList = [];

        foreach ($merchants as $merchant)
        {
            $orgIdList[] = $merchant[Merchant\Entity::ORG_ID];
        }

        $orgIdList = array_unique($orgIdList);

        return (new Admin\Org\Repository)
                    ->hasAnyOrgEnforced2Fa($orgIdList);
    }

    public function getMaskedContactMobile()
    {
        $mobile = $this->getContactMobile();

        if (empty($mobile) === false)
        {
            return mask_except_last4($mobile);
        }

        return $mobile;
    }

    public function getMaskedEmail()
    {
        $email = $this->getEmail();

        if (empty($email) === false)
        {
            return mask_email($email);
        }

        return $email;
    }

    protected function getRestrictedAttribute(): bool
    {
        $merchantIds = (new MerchantUser\Repository)->returnMerchantIdsForUserId($this->getAttribute(self::ID), 2);

        if (count($merchantIds) !== 1)
        {
            return false;
        }

        $merchant = (new Merchant\Repository)->find($merchantIds[0]);

        return $merchant->getRestricted();
    }


    public function getMerchantEntity()
    {
        $merchantIds = (new MerchantUser\Repository)->returnMerchantIdsForUserId($this->getAttribute(self::ID), 2);

        if (count($merchantIds) !== 1)
        {
            return null;
        }

        return (new Merchant\Repository)->find($merchantIds[0]);
    }

    public function setContactMobileNull()
    {
        $this->setAttribute(self::CONTACT_MOBILE, null);
    }

    public function setName($name)
    {
        $this->setAttribute(self::NAME, $name);
    }

    public function getAllSettings(): array
    {
        $settings = $this->getSettingsAccessor()->all()->toArray();
        // Merges defaults
        $settings += [Entity::SETTINGS_SKIP_CONTACT_MOBILE_VERIFY => '0'];

        return $settings;
    }

    public function toArrayMerchant()
    {
        $attributes = $this->toArrayPublic();

        $app = App::getFacadeRoot();

        $attributes[self::ROLE] = $this->getAttribute(self::PIVOT)->role;

        $attributes[self::ROLE_NAME] = $app['repo']->roles->fetchRoleName($attributes[self::ROLE]);

        return $attributes;
    }

    public function getUserId()
    {
        return $this->getAttribute(self::ID);
    }

    public function isOwner(): bool
    {
        return (new MerchantUser\Repository())->isOwnerForUserId($this->getId()) === true;
    }

    public function getIsOwnerMerchantIds()
    {
        return (new MerchantUser\Repository())->fetchMerchantIdForUserIdAndRole($this->getId());
    }

    public function getPrimaryMerchantIds()
    {
        return (new MerchantUser\Repository())->fetchPrimaryMerchantIdsForUserIdAndRole($this->getId());
    }

    public function getFirstMerchantEntity()
    {
        $merchantIds = (new MerchantUser\Repository)->returnMerchantIdsForUserId($this->getAttribute(self::ID), 1);
        return (new Merchant\Repository)->find($merchantIds[0]);
    }

    public function getFirstMerchantEntityForOrg($orgId)
    {
        $orgId = Org\Entity::verifyIdAndSilentlyStripSign($orgId);

        $dcs = app('dcs');

        $merchantIdsWithCrossOrgFeature =  $dcs->fetchEntityIdsByFeatureName(DcsConstants::CrossOrgLogin, Type::MERCHANT, $this->mode);

        app('trace')->info(TraceCode::FETCH_ENTITY_IDS_BY_FEATURE_NAME, [
            "Mids fetched from DCS for cross org login feature" => $merchantIdsWithCrossOrgFeature,
        ]);

        if (empty($merchantIdsWithCrossOrgFeature) ===  true) {
            $merchantIdsWithCrossOrgFeature = (new Feature\Repository)->findMerchantIdsHavingFeatures([Features::CROSS_ORG_LOGIN]);
        }

        $merchantIds = (new MerchantUser\Repository)->returnMerchantIdsForUserId($this->getAttribute(self::ID), 1000);

        $merchants = (new Merchant\Repository)->findMany($merchantIds);

        $filteredMerchants = new Base\PublicCollection;

        foreach ($merchants as $merchant)
        {
            if ($merchant->getOrgID() === $orgId or in_array($merchant->getId(), $merchantIdsWithCrossOrgFeature, true) === true)
            {
                $filteredMerchants->add($merchant);
            }
        }

        if(empty($filteredMerchants) === false)
        {
            return $filteredMerchants[0];
        }
        else
        {
            return null;
        }
    }
}
