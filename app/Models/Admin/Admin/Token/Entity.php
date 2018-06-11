<?php

namespace RZP\Models\Admin\Admin\Token;

use App;
use Carbon\Carbon;
use RZP\Models\Admin\Base;
use RZP\Models\Admin\Admin;
use RZP\Models\Base\Traits\HardDeletes;

class Entity extends Base\Entity
{
    use HardDeletes;

    const ID         = 'id';
    const ADMIN_ID   = 'admin_id';
    const TOKEN      = 'token';
    const EXPIRES_AT = 'expires_at';

    protected $entity = 'admin_token';

    protected $generateIdOnCreate = false;

    protected $fillable = [
        self::TOKEN,
        self::EXPIRES_AT
    ];

    protected $visible = [
        self::ID,
        self::ADMIN_ID,
        self::EXPIRES_AT,
        self::CREATED_AT,
        self::UPDATED_AT
    ];

    protected $public = [
        self::ID,
        self::ADMIN_ID,
        self::EXPIRES_AT
    ];

    protected $publicSetters = array(
        self::ADMIN_ID,
    );

    // ------------ Relations ------------
    public function admin()
    {
        return $this->belongsTo('RZP\Models\Admin\Admin\Entity');
    }

    // ---------- Public Setters ---------
    public function setPublicAdminIdAttribute(array & $attributes)
    {
        $attributes[self::ADMIN_ID] = Admin\Entity::getSignedId(
            $this->getAttribute(self::ADMIN_ID));
    }

    // -------------- Setters ------------
    public function setExpiresAt(int $timestamp)
    {
        $this->setAttribute(self::EXPIRES_AT, $timestamp);
    }

    // -------------- Getters ------------
    public function getAdminId()
    {
        return $this->getAttribute(self::ADMIN_ID);
    }

    public function getExpiresAt()
    {
        return $this->getAttribute(self::EXPIRES_AT);
    }

    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }

    /*
     * Returns an unexpired token
     */
    public function getValidToken()
    {
        $now = Carbon::now()->getTimestamp();

        if ($now >= $this->getExpiresAt())
        {
            return null;
        }

        return $this->getToken();
    }
}
