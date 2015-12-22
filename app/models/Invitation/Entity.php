<?php

namespace Models\Invitation;

use Models\Merchant;
use Models\Base;

class Entity extends Base\Entity
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'invitations';

    /**
     * The guarded attributes on the model.
     *
     * @var array
     */
    protected $guarded = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Get the merchant that owns the invitation.
     */
    public function merchant()
    {
        return $this->belongsTo(Merchant\Entity::class, 'merchant_id');
    }
}