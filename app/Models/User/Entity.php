<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Constants\Table;

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

    const ACTION                = 'action';
    const USER_ID               = 'user_id';
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

    public function merchants()
    {
        return $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                    ->withPivot(self::ROLE)
                    ->orderBy(self::NAME);
    }

    public function setConfirmTokenNull()
    {
        $this->setAttribute(self::CONFIRM_TOKEN, null);
    }

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }
}
