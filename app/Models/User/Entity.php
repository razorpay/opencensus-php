<?php

namespace RZP\Models\User;

use RZP\Models\Base;
use RZP\Models\Merchant;
use RZP\Models\Invitation;
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
    const MERCHANTS             = 'merchants';
    const ROLE                  = 'role';
    const PIVOT                 = 'pivot';
    const OWNER                 = 'owner';
    const CONFIRMED             = 'confirmed';
    const INVITATIONS           = 'invitations';

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
        self::CONFIRMED,
    	self::CREATED_AT,
    ];

    protected $hidden = [
        self::PASSWORD,
        self::REMEMBER_TOKEN,
        self::CONFIRM_TOKEN,
    ];

    protected $generateIdOnCreate = false;

    protected $appends = [self::CONFIRMED];

    public function merchants()
    {
        return $this->belongsToMany(Merchant\Entity::class, Table::MERCHANT_USERS)
                    ->withPivot(self::ROLE)
                    ->orderBy(self::NAME);
    }

    public function invitations()
    {
        return $this->hasMany(Invitation\Entity::class)
                    ->orderBy(Invitation\Entity::CREATED_AT, 'desc');
    }

    public function setConfirmTokenNull()
    {
        $this->setAttribute(self::CONFIRM_TOKEN, null);
    }

    public function getPassword()
    {
        return $this->getAttribute(self::PASSWORD);
    }

    public function getConfirmedAttribute()
    {
        return ($this->getAttribute(self::CONFIRM_TOKEN) === null);
    }

    public function toArrayMerchant()
    {
        $attributes = $this->toArrayPublic();

        $attributes[self::ROLE] = $this->getAttribute(self::PIVOT)->role;

        return $attributes;
    }
}
