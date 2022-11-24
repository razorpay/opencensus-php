<?php

namespace RZP\Models\P2p\Vpa\Handle;

use Database\Factories\P2pHandleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RZP\Base\BuilderEx;
use RZP\Models\P2p\Base;
use RZP\Models\Merchant;
use RZP\Models\P2p\Client;

class Entity extends Base\Entity
{
    use HasFactory;

    const CODE         = 'code';
    const MERCHANT_ID  = 'merchant_id';
    const BANK         = 'bank';
    const ACQUIRER     = 'acquirer';
    const ACTIVE       = 'active';

    /****************** Input Keys ***************/
    const BANK_NAME    = 'bank_name';
    const TXN_PREFIX   = 'txn_prefix';
    const CLIENT       = 'client';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_handle';
    protected $primaryKey         = self::CODE;
    protected $generateIdOnCreate = false;
    protected static $generators  = [];

    /**
     * Will be forced attached with setClient,
     * The default will be null as it is not set.
     */
    protected $client             = null;

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
        Entity::CODE,
        Entity::MERCHANT_ID,
        Entity::BANK,
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
        Entity::ACTIVE,
        Entity::CREATED_AT,
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

    public function getTxnPrefix(string $merchantId)
    {
        // Currently hardcoding for the map we have
        // Later we need to find a way for separate prefix for a handle and merchant
        $map = [
            // Test Cases
            'razoraxis' => 'RRA',
            // Stage
            'bajaj'     => 'BJJ',
            // Prod
            'abfspay'   => 'BJJ',
        ];

        return array_get($map, $this->getCode(), 'TST');
    }

    public function getMaxAllowedVpas(string $merchantId): int
    {
        // Currently we are hardcoding to 3 for all merchant
        // Later we need to find a way for separate limit for a handle and merchant
        return 5;
    }

    public function isAllowedToMerchant(string $merchantId): bool
    {
        return in_array($this->getMerchantId(), [$merchantId, Merchant\Account::SHARED_ACCOUNT], true);
    }

    /******************* Relations **************/

    public function clients()
    {
        return $this->hasMany(Client\Entity::class, Client\Entity::HANDLE);
    }

    /**
     * @param Client\Entity $client
     */
    public function setClient(Client\Entity $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client\Entity
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @param $clientId
     * @return Client\Entity
     */
    public function client(string $type, string $clientId)
    {
        return $this->clients()->where(
            [
               Client\Entity::HANDLE        => $this->getCode(),
               Client\Entity::CLIENT_TYPE   => $type,
               Client\Entity::CLIENT_ID     => $clientId
            ])->first();
    }

    protected static function newFactory(): P2pHandleFactory
    {
        return P2pHandleFactory::new();
    }
}
