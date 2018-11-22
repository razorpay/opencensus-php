<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Models\P2p\Base;
use RZP\Models\Merchant;

class Entity extends Base\Entity
{
    const HANDLE       = 'handle';
    const MERCHANT_ID  = 'merchant_id';
    const ACQUIRER     = 'acquirer';
    const ACTIVE       = 'active';

    /************** Entity Properties ************/

    public $incrementing          = true;
    protected $entity             = 'p2p_handle';
    protected $primaryKey         = 'handle';
    protected $generateIdOnCreate = false;
    protected static $generators  = [];

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::HANDLE,
        Entity::MERCHANT_ID,
        Entity::ACQUIRER,
        Entity::ACTIVE,
    ];

    protected $visible = [
        Entity::HANDLE,
        Entity::MERCHANT_ID,
        Entity::ACQUIRER,
        Entity::ACTIVE,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::HANDLE,
        Entity::MERCHANT_ID,
        Entity::ACQUIRER,
        Entity::ACTIVE,
        Entity::CREATED_AT,
    ];

    protected $defaults = [
        Entity::HANDLE       => null,
        Entity::MERCHANT_ID  => null,
        Entity::ACQUIRER     => null,
        Entity::ACTIVE       => null,
    ];

    protected $casts = [
        Entity::HANDLE       => 'string',
        Entity::MERCHANT_ID  => 'string',
        Entity::ACQUIRER     => 'string',
        Entity::ACTIVE       => 'bool',
        Entity::CREATED_AT   => 'int',
        Entity::UPDATED_AT   => 'int',
    ];

    /***************** SETTERS *****************/

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
    public function setMerchantId(string $merchantId)
    {
        return $this->setAttribute(self::MERCHANT_ID, $merchantId);
    }

    /**
     * @return $this
     */
    public function setAcquirer(string $acquirer)
    {
        return $this->setAttribute(self::ACQUIRER, $acquirer);
    }

    /**
     * @return $this
     */
    public function setActive(bool $active)
    {
        return $this->setAttribute(self::ACTIVE, $active);
    }

    /***************** GETTERS *****************/

    /**
     * @return string self::HANDLE
     */
    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    /**
     * @return string self::MERCHANT_ID
     */
    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    /**
     * @return string self::ACQUIRER
     */
    public function getAcquirer()
    {
        return $this->getAttribute(self::ACQUIRER);
    }

    /**
     * @return bool self::ACTIVE
     */
    public function isActive()
    {
        return $this->getAttribute(self::ACTIVE);
    }

    public function isAllowedToMerchant(string $merchantId): bool
    {
        return in_array($this->getMerchantId(), [$merchantId, Merchant\Account::SHARED_ACCOUNT], true);
    }
}
