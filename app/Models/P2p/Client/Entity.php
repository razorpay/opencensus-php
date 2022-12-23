<?php

namespace RZP\Models\P2p\Client;

use Crypt;
use Database\Factories\P2pClientFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use RZP\Models\P2p\Base;

class Entity extends Base\Entity
{
    use HasFactory;

    const ID                = 'id';
    const HANDLE            = 'handle';
    const CLIENT_TYPE       = 'client_type';
    const CLIENT_ID         = 'client_id';
    const SECRETS           = 'secrets';
    const CONFIG            = 'config';
    const GATEWAY_DATA      = 'gateway_data';

    /*************** Constants********************/
    const CUSTOMER          = 'customer';
    const CUSTOMER_ID       = 'customer_id';
    const GATEWAY_CONFIG    = 'gateway_config';
    const GATEWAYS          = 'gateways';
    const TOKEN             = 'token';

    /************** Entity Properties ************/

    protected $entity             = 'p2p_client';
    protected static $sign        = 'client';
    protected $generateIdOnCreate = true;

    protected $dates = [
        Entity::CREATED_AT,
        Entity::UPDATED_AT,
    ];

    protected $fillable = [
       self::HANDLE,
       self::CLIENT_ID,
       self::CLIENT_TYPE,
       self::SECRETS,
       self::CONFIG,
       self::GATEWAY_DATA
    ];

    protected $visible = [
        self::ID,
        self::HANDLE,
        self::CLIENT_TYPE,
        self::CLIENT_ID,
        self::CONFIG,
        self::GATEWAY_DATA,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $public = [
        self::ENTITY,
        self::ID,
        self::HANDLE,
        self::CLIENT_TYPE,
        self::CLIENT_ID,
        self::CONFIG,
        self::GATEWAY_DATA,
        self::CREATED_AT,
        self::UPDATED_AT,
    ];

    protected $casts = [
        self::CONFIG        => 'array',
        self::GATEWAY_DATA  => 'array',
        self::SECRETS       => 'array',
    ];

    protected $defaults = [
        self::CONFIG       => null,
        self::SECRETS      => null,
        self::GATEWAY_DATA => null
    ];

    /************** Getters ************/

    public function getHandle()
    {
        return $this->getAttribute(self::HANDLE);
    }

    public function getClientId()
    {
        return $this->getAttribute(self::CLIENT_ID);
    }

    public function getClientType()
    {
        return $this->getAttribute(self::CLIENT_TYPE);
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->getAttribute(self::CONFIG);
    }

    /**
     * @return Secrets
     */
    public function getSecrets()
    {
        return $this->getAttribute(self::SECRETS);
    }

    /**
     * @return GatewayData
     */
    public function getGatewayData()
    {
        return $this->getAttribute(self::GATEWAY_DATA);
    }

    /************** Setters ************/

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
    public function setClientId(string $clientId)
    {
        return $this->setAttribute(self::CLIENT_ID, $clientId);
    }

    /**
     * @return $this
     */
    public function setClientType(string $clientType)
    {
        return $this->setAttribute(self::CLIENT_TYPE, $clientType);
    }

    /**
     * @return $this
     */
    public function setConfig($config)
    {
        return $this->setAttribute(self::CONFIG, $config);
    }

    /**
     * @return $this
     */
    public function setGatewayData($gatewayData)
    {
        return $this->setAttribute(self::GATEWAY_DATA, $gatewayData);
    }

    /********** Helper Functions *************/
    public function getConfigValue(string $key)
    {
        return $this->getConfig()->get($key);
    }

    public function getGatewayDataValue(string $key)
    {
        return $this->getGatewayData()->get($key);
    }

    /********* Entity Views ******************/
    public function toArrayWithSecrets()
    {
        $client = $this->toArray();

        $client[self::SECRETS] = $this->getSecretsAttribute()->toArrayDecrypted();

        return $client;
    }

    /********** Protected ****************/

    protected function getGatewayDataAttribute()
    {
        $gatewayData = $this->attributes[self::GATEWAY_DATA];

        return GatewayData::fromJson($gatewayData);
    }

    protected function getConfigAttribute()
    {
        $config = $this->attributes[self::CONFIG];

        return Config::fromJson($config);
    }

    protected function getSecretsAttribute()
    {
        $secrets = $this->attributes[self::SECRETS];

        return Secrets::fromJson($secrets);
    }

    protected function setSecretsAttribute($data)
    {
        if ($data === null)
        {
            $data = [];
        }

        $secrets = (new Secrets($data))->encrypt();

        $this->attributes[self::SECRETS] = $secrets->toJson();
    }

    protected static function newFactory(): P2pClientFactory
    {
        return P2pClientFactory::new();
    }
}
