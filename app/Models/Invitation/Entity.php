<?php

namespace RZP\Models\Invitation;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\User;
use RZP\Models\Merchant;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const USER_ID      = 'user_id';
    const EMAIL        = 'email';
    const TOKEN        = 'token';
    const ROLE         = 'role';
    const DELETED_AT   = 'deleted_at';

    // Other constants
    const ACTION        = 'action';
    const SENDER_NAME   = 'sender_name';
    const MERCHANT_NAME = 'merchant_name';

    const TOKEN_LENGTH = 40;

    protected $entity  = 'invitation';

    public $incrementing = true;

    protected $public = [
        self::ID,
        self::EMAIL,
        self::ROLE,
        self::USER_ID,
        self::MERCHANT_ID,
    ];

    protected $fillable = [
        self::ROLE,
        self::EMAIL,
        self::TOKEN,
    ];

    protected $hidden = [
        self::TOKEN
    ];

    /**
     * Get the merchant that owns the invitation.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class);
    }

    /**
     * Get the user that received the invitation.
     */
    public function user()
    {
        return $this->belongsTo(User\Entity::class);
    }

    public function getRole()
    {
        return $this->getAttribute(self::ROLE);
    }

    public function getEmail()
    {
        return $this->getAttribute(self::EMAIL);
    }

    public function getUserId()
    {
        return $this->getAttribute(self::USER_ID);
    }

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }

    public function toArrayUser()
    {
        $attributes = [
            self::ID            => $this->getAttribute(self::ID),
            self::EMAIL         => $this->getAttribute(self::EMAIL),
            self::ROLE          => $this->getAttribute(self::ROLE),
            self::USER_ID       => $this->getAttribute(self::USER_ID),
            self::MERCHANT_ID   => $this->getAttribute(self::MERCHANT_ID),
            self::MERCHANT_NAME => $this->merchant->getName(),
        ];

        return $attributes;
    }
}
