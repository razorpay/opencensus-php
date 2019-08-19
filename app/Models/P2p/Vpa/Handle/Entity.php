<?php

namespace RZP\Models\P2p\Vpa\Handle;

use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base;
use RZP\Models\Merchant;

class Entity extends Base\Entity
{
    use Base\Traits\HasMerchant;

    const CODE         = 'code';
    const MERCHANT_ID  = 'merchant_id';
    const BANK         = 'bank';
    const ACQUIRER     = 'acquirer';
    const ACTIVE       = 'active';

    /****************** Input Keys ***************/
    const BANK_NAME    = 'bank_name';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_handle';
    protected $primaryKey         = self::CODE;
    protected $generateIdOnCreate = false;
    protected static $generators  = [];

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::ACQUIRER,
        Entity::ACTIVE,
    ];

    protected $visible = [
        Entity::CODE,
        Entity::MERCHANT_ID,
        Entity::BANK,
        Entity::ACQUIRER,
        Entity::ACTIVE,
        Entity::CREATED_AT,
    ];

    protected $public = [
        Entity::ENTITY,
        Entity::CODE,
        Entity::BANK,
    ];

    protected $defaults = [
        Entity::ACTIVE       => true,
    ];

    protected $casts = [
        Entity::CODE         => 'string',
        Entity::MERCHANT_ID  => 'string',
        Entity::BANK         => 'string',
        Entity::ACQUIRER     => 'string',
        Entity::ACTIVE       => 'bool',
        Entity::CREATED_AT   => 'int',
        Entity::UPDATED_AT   => 'int',
    ];

    /**************** OVERRIDDEN ****************/

    public static function verifyUniqueId($id, $throw = true)
    {
        return false;
    }

    /***************** SETTERS *****************/

    /**
     * @return $this
     */
    public function setCode(string $handle)
    {
        return $this->setAttribute(self::CODE, $handle);
    }

    /**
     * @return $this
     */
    public function setBank(string $bank)
    {
        return $this->setAttribute(self::BANK, $bank);
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
     * @return string self::CODE
     */
    public function getCode()
    {
        return $this->getAttribute(self::CODE);
    }

    /**
     * @return string self::MERCHANT_ID
     */
    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    /**
     * @return string self::BANK
     */
    public function getBank()
    {
        return $this->getAttribute(self::BANK);
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

    public function getMaxAllowedVpas(string $merchantId): int
    {
        // Currently we are hardcoding to 3 for all merchant
        // Later we need to find a way for separate limit for a handle and merchant
        return 3;
    }

    public function isAllowedToMerchant(string $merchantId): bool
    {
        return in_array($this->getMerchantId(), [$merchantId, Merchant\Account::SHARED_ACCOUNT], true);
    }

    public function scopeMerchant(BuilderEx $query, Merchant\Entity $merchant)
    {
        return $query->whereIn(self::MERCHANT_ID, [$merchant->getId(), Merchant\Account::SHARED_ACCOUNT]);
    }
}
