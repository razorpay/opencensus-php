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
    const ACTION       = 'action';

    protected $entity  = 'invitation';

    const TOKEN_LENGTH = 40;

    protected $public = [
        self::ID,
        self::EMAIL,
        self::ROLE,
        self::USER_ID,
        self::MERCHANT_ID,
    ];

    protected $fillable = [
        self::ID,
        self::EMAIL,
        self::TOKEN,
        self::ROLE,
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
}
