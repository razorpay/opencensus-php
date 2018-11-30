<?php

namespace RZP\Models\User;

use Hash;
use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Settings;
use RZP\Constants\Table;
use RZP\Models\Invitation;

class Entity extends Base\PublicEntity
{
    const ID                    = 'id';
    const NAME                  = 'name';
    const EMAIL                 = 'email';
    const PASSWORD              = 'password';
    const OLD_PASSWORD          = 'old_password';
    const PASSWORD_CONFIRMATION = 'password_confirmation';
    const CONTACT_MOBILE        = 'contact_mobile';
    const REMEMBER_TOKEN        = 'remember_token';
    const CONFIRM_TOKEN         = 'confirm_token';
    const PASSWORD_RESET_TOKEN  = 'password_reset_token';
    const PASSWORD_RESET_EXPIRY = 'password_reset_expiry';
    const CAPTCHA               = 'captcha';
    const CAPTCHA_DISABLE       = 'captcha_disable';

    const TOKEN                 = 'token';
    const EXPIRY_TIME           = 'expiryTime';

    const ACTION                = 'action';
    const USER_ID               = 'user_id';
    const MERCHANT_ID           = 'merchant_id';
    const MERCHANTS             = 'merchants';
    const ROLE                  = 'role';
    const PIVOT                 = 'pivot';
    const OWNER                 = 'owner';
    const CONFIRMED             = 'confirmed';
    const INVITATIONS           = 'invitations';

    const PASSWORD_TOKEN_LENGTH = 50;

    // Boolean attribute is true if contact mobile is verified via OTP
    const CONTACT_MOBILE_VERIFIED = 'contact_mobile_verified';
    // Additional input keys
    const MEDIUM                 = 'medium';
    const OTP                    = 'otp';
    const ACTION_VERIFY_CONTACT  = 'verify_contact';
    const MEDIUM_SMS             = 'sms';
    const MEDIUM_EMAIL           = 'email';
    const SETTINGS               = 'settings';

    // Settings keys
    const SETTINGS_SKIP_CONTACT_MOBILE_VERIFY = 'skip_contact_mobile_verify';

    protected $entity = 'user';

    protected $fillable = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::PASSWORD,
        self::CONTACT_MOBILE,
        self::REMEMBER_TOKEN,
        self::CONFIRM_TOKEN,
        self::PASSWORD_RESET_TOKEN,
        self::PASSWORD_RESET_EXPIRY,
    ];

    protected $public = [
        self::ID,
        self::NAME,
        self::EMAIL,
        self::CONTACT_MOBILE,
        self::CONTACT_MOBILE_VERIFIED,
        self::CONFIRMED,
        self::CREATED_AT,
    ];

    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN,
        self::CONFIRM_TOKEN,
        self::PASSWORD_RESET_TOKEN,
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
        self::CONTACT_MOBILE_VERIFIED => 'bool',
    ];

    protected $generateIdOnCreate = true;

    protected $appends = [self::CONFIRMED];

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
                    ->withPivot(self::ROLE)
                    ->orderByRaw($sql, [$this->getEmail()]);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation\Entity::class, Invitation\Entity::EMAIL, Entity::EMAIL)
                    ->orderBy(Invitation\Entity::CREATED_AT, 'desc');
    }

    public function setConfirmTokenNull()
    {
        $this->setAttribute(self::CONFIRM_TOKEN, null);
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

    protected function setPasswordAttribute($password)
    {
        $this->attributes[self::PASSWORD] = Hash::make($password);
    }

    public function getContactMobile()
    {
        return $this->getAttribute(self::CONTACT_MOBILE);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
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

    public function isContactMobileVerified(): bool
    {
        return ($this->getAttribute(self::CONTACT_MOBILE_VERIFIED) === true);
    }

    public function setContactMobileVerified(bool $verified)
    {
        $this->setAttribute(self::CONTACT_MOBILE_VERIFIED, $verified);
    }

    public function getSettingsAccessor(): Settings\Accessor
    {
        return Settings\Accessor::for($this, Settings\Module::USER);
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

        $attributes[self::ROLE] = $this->getAttribute(self::PIVOT)->role;

        return $attributes;
    }
}
