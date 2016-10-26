<?php

namespace App\Invitation;

use App\Merchant;
use App\Base;

class Entity extends Base\Entity
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'invitations';

    protected $hidden = [
        'token'
    ];

    protected $fillable = [
        'user_id',
        'email',
        'token',
        'role'
    ];

    /**
     * Get the merchant that owns the invitation.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class, 'merchant_id');
    }
}
