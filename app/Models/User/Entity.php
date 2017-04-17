<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Models\Merchant;

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

    // For merchant-user pivot table
    const MERCHANT_USERS        = 'merchant_users';
    const USER_ID               = 'user_id';

    const ACTION                = 'action';
    const MERCHANT_ID           = 'merchant_id';
    const ROLE                  = 'role';
    const OWNER                 = 'owner';

    protected $entity = 'user';

    protected $fillable = [
    	self::ID,
    	self::NAME,
    	self::EMAIL,
    	self::PASSWORD,
    	self::CONTACT_MOBILE,
    	self::REMEMBER_TOKEN,
    	self::CONFIRM_TOKEN
    ];

    protected $public = [
    	self::ID,
    	self::NAME,
    	self::EMAIL,
    	self::CONTACT_MOBILE,
    	self::CREATED_AT,
    ];

    protected $generateIdOnCreate = false;

    /**
     * Determine if the user is a member of any merchants.
     *
     * @return bool
     */
    public function hasMerchants()
    {
        return (count($this->merchants) > 0);
    }

    /**
     * Get all of the merchants that the user belongs to.
     */
    public function merchants()
    {
        $query = $this->belongsToMany(
                            Merchant\Entity::class,
                            self::MERCHANT_USERS,
                            self::USER_ID,
                            self::MERCHANT_ID)
                      ->withPivot(self::ROLE);

        return $query->orderBy(self::NAME, 'asc');
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken()
    {
        return $this->getAttribute(self::REMEMBER_TOKEN);
    }

    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value)
    {
        $this->setAttribute(self::REMEMBER_TOKEN, $value);
    }

    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName()
    {
        return $this->getAttribute(self::REMEMBER_TOKEN);
    }

    /**
     * Get the e-mail address where password reminders are sent.
     *
     * @return string
     */
    public function getReminderEmail()
    {
        return $this->email;
    }

    public function setConfirmTokenNull()
    {
        $this->setAttribute(self::CONFIRM_TOKEN, null);
    }

    public function setPassword(string $password)
    {
        $this->setAttribute(self::PASSWORD, $password);
    }

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    /**
     * Determine if the given merchant is owned by the user.
     *
     * @param  \RZP\Models\Merchant\Entity  $merchant
     * @return bool
     */
    public function ownsMerchant($merchant)
    {
        $merchant = $this->merchants()
                         ->where(self::MERCHANT_ID, $merchant['id'])
                         ->where(self::ROLE, self::OWNER)
                         ->first();

        return !is_null($merchant);
    }

    /**
     * Get the user's role on a given merchant.
     *
     * @param  \RZP\Models\Merchant\Entity  $merchant
     * @return string
     */
    public function getMerchantRole($merchant)
    {
        $merchant = $this->merchants->find($merchant->id);

        if ($merchant)
        {
            return $merchant->pivot->role;
        }
    }

    /**
     * Generates a one time use token of the given length
     */
    protected function generateOneTimeUseToken($length)
    {
        $bytes = random_bytes($length/2);

        $token = bin2hex($bytes);

        return $token;
    }

    /**
     * Generates Confirmation token
     */
    protected function generateConfirmToken()
    {
        $this->setAttribute(self::CONFIRM_TOKEN, $this->generateOneTimeUseToken(32));
    }
}
