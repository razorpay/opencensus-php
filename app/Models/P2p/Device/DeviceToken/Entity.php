<?php

namespace RZP\Models\P2p\Device\DeviceToken;

use RZP\Models\P2p\Base;

class Entity extends Base\Entity
{
    const DEVICE_ID        = 'device_id';
    const HANDLE           = 'handle';
    const GATEWAY_DATA     = 'gateway_data';
    const STATUS           = 'status';
    const CL_CAPABILITY    = 'cl_capability';
    const CL_TOKEN         = 'cl_token';
    const CL_PAYLOAD       = 'cl_payload';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_device_token';
    protected static $sign        = 'device_token';
    protected $generateIdOnCreate = false;
    protected static $generators  = [];

    protected $dates = [
        Entity::REFRESHED_AT,
        Entity::DELETED_AT,
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::CL_CAPABILITY,
        Entity::CL_TOKEN,
        Entity::CL_PAYLOAD,
    ];

    protected $visible = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::CL_CAPABILITY,
        Entity::CL_TOKEN,
        Entity::CL_PAYLOAD,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::GATEWAY_DATA,
        Entity::STATUS,
        Entity::CL_CAPABILITY,
        Entity::CL_TOKEN,
        Entity::CL_PAYLOAD,
        Entity::REFRESHED_AT,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::DEVICE_ID        => null,
        Entity::HANDLE           => null,
        Entity::GATEWAY_DATA     => null,
        Entity::STATUS           => null,
        Entity::CL_CAPABILITY    => null,
        Entity::CL_TOKEN         => null,
        Entity::CL_PAYLOAD       => null,
    ];

    protected $casts = [
        Entity::ID               => 'string',
        Entity::DEVICE_ID        => 'string',
        Entity::HANDLE           => 'string',
        Entity::GATEWAY_DATA     => 'array',
        Entity::STATUS           => 'string',
        Entity::CL_CAPABILITY    => 'string',
        Entity::CL_TOKEN         => 'string',
        Entity::CL_PAYLOAD       => 'string',
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
    public function setClCapability(string $clCapability)
    {
        return $this->setAttribute(self::CL_CAPABILITY, $clCapability);
    }

    /**
     * @return $this
     */
    public function setClToken(string $clToken)
    {
        return $this->setAttribute(self::CL_TOKEN, $clToken);
    }

    /**
     * @return $this
     */
    public function setClPayload(string $clPayload)
    {
        return $this->setAttribute(self::CL_PAYLOAD, $clPayload);
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

    /**
     * @return string self::CL_CAPABILITY
     */
    public function getClCapability()
    {
        return $this->getAttribute(self::CL_CAPABILITY);
    }

    /**
     * @return string self::CL_TOKEN
     */
    public function getClToken()
    {
        return $this->getAttribute(self::CL_TOKEN);
    }

    /**
     * @return string self::CL_PAYLOAD
     */
    public function getClPayload()
    {
        return $this->getAttribute(self::CL_PAYLOAD);
    }
}
