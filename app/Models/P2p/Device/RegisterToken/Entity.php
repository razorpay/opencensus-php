<?php

namespace RZP\Models\P2p\Device\RegisterToken;

use Database\Factories\P2pRegisterTokenFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RZP\Models\P2p\Base;
use RZP\Models\P2p\Base\Traits;

class Entity extends Base\Entity
{
    use HasFactory;
    use Traits\HasMerchant;
    use Traits\HasHandle;

    const TOKEN        = 'token';
    const MERCHANT_ID  = 'merchant_id';
    const DEVICE_ID    = 'device_id';
    const HANDLE       = 'handle';
    const STATUS       = 'status';
    const DEVICE_DATA  = 'device_data';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_register_token';
    protected $primaryKey         = 'token';
    protected $generateIdOnCreate = true;

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::DEVICE_DATA,
    ];

    protected $visible = [
        Entity::TOKEN,
        Entity::MERCHANT_ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::STATUS,
        Entity::DEVICE_DATA,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::TOKEN,
        Entity::MERCHANT_ID,
        Entity::DEVICE_ID,
        Entity::HANDLE,
        Entity::STATUS,
        Entity::DEVICE_DATA,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::STATUS       => 'created',
        Entity::DEVICE_DATA  => [],
    ];

    protected $casts = [
        Entity::TOKEN        => 'string',
        Entity::MERCHANT_ID  => 'string',
        Entity::DEVICE_ID    => 'string',
        Entity::HANDLE       => 'string',
        Entity::STATUS       => 'string',
        Entity::DEVICE_DATA  => 'array',
        Entity::CREATED_AT   => 'int',
        Entity::UPDATED_AT   => 'int',
    ];

    /***************** GENERATORS **************/

    public static function generateUniqueId()
    {
        return gen_uuid();
    }

    public static function verifyUniqueId($id, $throw = true)
    {
        return false;
    }

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setToken(string $token)
    {
        return $this->setAttribute(self::TOKEN, $token);
    }

    /**
     * @return $this
     */
    public function setMerchantId(string $merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

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
    public function setStatus(string $status)
    {
        return $this->setAttribute(self::STATUS, $status);
    }

    /**
     * @return $this
     */
    public function setDeviceData(array $deviceData)
    {
        return $this->setAttribute(self::DEVICE_DATA, $deviceData);
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::TOKEN
     */
    public function getToken()
    {
        return $this->getAttribute(self::TOKEN);
    }

    /**
     * @return string self::MERCHANT_ID
     */
    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

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
     * @return string self::STATUS
     */
    public function getStatus()
    {
        return $this->getAttribute(self::STATUS);
    }

    /**
     * @return array self::DEVICE_DATA
     */
    public function getDeviceData()
    {
        return $this->getAttribute(self::DEVICE_DATA);
    }

    /**
     * @return bool Whether the register token is completed
     */
    public function isCompleted(): bool
    {
        return ($this->getStatus() === Status::COMPLETED);
    }

    protected static function newFactory(): P2pRegisterTokenFactory
    {
        return P2pRegisterTokenFactory::new();
    }
}
