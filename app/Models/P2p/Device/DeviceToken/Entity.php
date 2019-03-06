<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Device;
use RZP\Models\P2p\Device\RegisterToken;

class Entity extends Base\Entity
{
    use Base\Traits\HasDevice;
    use Base\Traits\HasHandle;

    const DEVICE_ID        = 'device_id';
    const HANDLE           = 'handle';
    const GATEWAY_DATA     = 'gateway_data';
    const SDK_DATA         = 'sdk_data';
    const STATUS           = 'status';

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
        Entity::SDK_DATA,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::SDK_DATA,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::SDK_DATA,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::GATEWAY_DATA     => [],
        Entity::STATUS           => RegisterToken\Status::VERIFIED,
        Entity::SDK_DATA         => [],
    ];

    protected $casts = [
        Entity::ID               => 'string',
        Entity::DEVICE_ID        => 'string',
        Entity::HANDLE           => 'string',
        Entity::GATEWAY_DATA     => 'array',
        Entity::STATUS           => 'string',
        Entity::SDK_DATA         => 'array',
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
    public function setHandle(string $handle)
    {
        return $this->setAttribute(self::HANDLE, $handle);
    }

    /**
     * @return $this
     */
    public function setGatewayData(array $gatewayData)
    {
        return $this->setAttribute(self::GATEWAY_DATA, $gatewayData);
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

    /**
     * @return $this
     */
    public function setSdkData(array $sdkData)
    {
        return $this->setAttribute(self::SDK_DATA, $sdkData);
    }

    /**
     * @return $this
     */
    public function mergeSdkData(array $sdkData)
    {
        return $this->setSdkData(array_merge($this->getSdkData(), $sdkData));
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
     * @return string self::HANDLE
     */
    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    /**
     * @return array self::GATEWAY_DATA
     */
    public function getGatewayData()
    {
        return $this->getAttribute(self::GATEWAY_DATA);
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

    /**
     * @return string self::SDK_DATA
     */
    public function getSdkData()
    {
        return $this->getAttribute(self::SDK_DATA);
    }

    /***************** SCOPES *****************/

    public function scopeVerified(BuilderEx $query)
    {
        return $query->where(self::STATUS, RegisterToken\Status::VERIFIED);
    }
}
