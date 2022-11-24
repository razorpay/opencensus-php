<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use Database\Factories\P2pDeviceTokenFactory;
use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Device\RegisterToken;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Entity extends Base\Entity
{
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;
    use Base\Traits\SoftDeletes;
    use HasFactory;

    const DEVICE_ID        = 'device_id';
    const HANDLE           = 'handle';
    const GATEWAY_DATA     = 'gateway_data';
    const STATUS           = 'status';

    /***************** Input Keys ****************/
    const DEVICE_TOKEN     = 'device_token';
    const EXPIRE_AT        = 'expire_at';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_device_token';
    protected static $sign        = 'device_token';
    protected $generateIdOnCreate = true;
    protected static $generators  = [
        Entity::REFRESHED_AT,
    ];

    protected $dates = [
        Entity::REFRESHED_AT,
        Entity::DELETED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::GATEWAY_DATA,
        Entity::STATUS,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::GATEWAY_DATA     => [],
        Entity::STATUS           => RegisterToken\Status::VERIFIED,
    ];

    protected $casts = [
        Entity::ID               => 'string',
        Entity::DEVICE_ID        => 'string',
        Entity::HANDLE           => 'string',
        Entity::GATEWAY_DATA     => 'array',
        Entity::STATUS           => 'string',
        Entity::REFRESHED_AT     => 'int',
        Entity::DELETED_AT       => 'int',
        Entity::CREATED_AT       => 'int',
        Entity::UPDATED_AT       => 'int',
    ];

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setDeviceId(string $deviceId)
    {
        return $this->setAttribute(self::DEVICE_ID, $deviceId);
    }

    /**
     * @return $this
     */
    public function setStatus(string $status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    /**
     * @return $this
     */
    public function setStatusExpired()
    {
        return $this->setStatus(RegisterToken\Status::EXPIRED);
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::DEVICE_ID
     */
    public function getDeviceId()
    {
        return $this->getAttribute(self::DEVICE_ID);
    }

    /**
     * @return string self::STATUS
     */
    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    public function isExpired()
    {
        return ($this->getStatus() === RegisterToken\Status::EXPIRED);
    }

    public function getExpireAt()
    {
        // By Default we are considering 9 minutes as for axis is 10 minutes
        $defaultExpireAt = $this->getRefreshedAt() + 540;

        // Expire at is gateway dependent, thus we can store in gateway_data
        return ($this->getGatewayData()[self::EXPIRE_AT] ?? $defaultExpireAt);
    }

    public function shouldRefresh()
    {
        $expireAt = $this->getExpireAt();
        $currentTime = $this->freshTimestamp();

        // We are giving one minute window, means even if token is about
        // to expire in next 1 one minute we will refresh it anyways
        return (($expireAt - ($currentTime + 60)) < 0);
    }

    /***************** SCOPES *****************/

    public function scopeVerified(BuilderEx $query)
    {
        return $query->where(self::STATUS, RegisterToken\Status::VERIFIED);
    }

    protected static function newFactory(): P2pDeviceTokenFactory
    {
        return P2pDeviceTokenFactory::new();
    }
}
