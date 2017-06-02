<?php

namespace RZP\Models\Gateway\Rule;

use Illuminate\Database\Eloquent\SoftDeletes;

use RZP\Models\Base;
use RZP\Models\Payment\Gateway;
use RZP\Models\Payment\Method;
use RZP\Models\Terminal;

class Entity extends Base\PublicEntity
{
    use SoftDeletes;

    const MERCHANT_ID      = 'merchant_id';
    const GATEWAY          = 'gateway';
    const LOAD             = 'load';
    const GATEWAY_ACQUIRER = 'gateway_acquirer';
    const INTERNATIONAL    = 'international';
    const METHOD           = 'method';
    const METHOD_TYPE      = 'method_type';
    const NETWORK          = 'network';
    const ISSUER           = 'issuer';
    const DELETED_AT       = 'deleted_at';

    const MAX_LOAD = 10000;

    /**
     * Attributes used for comparing terminal to rule
     */
    const COMPARISON_ATTRIBUTES = [
        self::GATEWAY,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
    ];

    /**
     * Attributes for which the value can be null, signifying any/all values
     * are acceptable for comparison
     */
    const NULLABLE_ATTRIBUTES = [
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
    ];

    protected $entity = 'gateway_rule';

    protected $generateIdOnCreate = true;

    protected $casts = [
        self::INTERNATIONAL => 'boolean',
        self::LOAD          => 'int',
    ];

    protected $fillable = [
        self::MERCHANT_ID,
        self::GATEWAY,
        self::LOAD,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::ISSUER,
    ];

    protected $visible = [
        self::ID,
        self::MERCHANT_ID,
        self::GATEWAY,
        self::LOAD,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

    protected static $modifiers = [
        self::NETWORK,
        self::ISSUER
    ];

    protected $publicSetters = [
        Entity::LOAD
    ];

    protected function modifyLoad(& $input)
    {
        $load = $input[self::LOAD];

        if (empty($load) === false)
        {
            $input[self::LOAD] = intval(round($load * 100));
        }
    }

    public function getLoad()
    {
        return $this->getAttribute(self::LOAD);
    }

    public function getMerchantId()
    {
        return $this->getAttribute(self::MERCHANT_ID);
    }

    public function getMethodType()
    {
        return $this->getAttribute(self::METHOD_TYPE);
    }

    public function getNetwork()
    {
        return $this->getAttribute(self::NETWORK);
    }

    public function getIssuer()
    {
        return $this->getAttribute(self::ISSUER);
    }

    public function getGateway()
    {
        return $this->getAttribute(self::GATEWAY);
    }

    public function getGatewayAcquirer()
    {
        return $this->getAttribute(self::GATEWAY_ACQUIRER);
    }

    public function isInternational()
    {
        return $this->getAttribute(self::INTERNATIONAL);
    }

    //----------------- Public Setters------------------------------------------

    public function setPublicLoadAttribute(array & $array)
    {
        $load = round(($this->getAttribute(self::LOAD) / 100), 2);

        $array[self::LOAD] = $load;
    }

    //---------------- Public Setters End---------------------------------------

    //----------------------------Modifiers-------------------------------------

    protected function modifyNetwork(array & $input)
    {
        if (empty($input[self::NETWORK]) === false)
        {
            $input[self::NETWORK] = strtoupper($input[self::NETWORK]);
        }
    }

    protected function modifyIssuer(array & $input)
    {
        if (empty($input[self::ISSUER]) === false)
        {
            $input[self::ISSUER] = strtoupper($input[self::ISSUER]);
        }
    }

    //----------------------------Modifiers End---------------------------------

    //---------------- Mutators-------------------------------------------------

    public function setLoadAttribute($load)
    {
        $this->attributes[self::LOAD] = intval(round($load * 100));
    }

    //----------------- Mutators End--------------------------------------------

    /**
     * Evaluates if a rule's terminal related attributes match those of
     * given terminal
     *
     * @param  Terminal\Entity $terminal Terminal entity to compare against
     * @return bool whether rule matches terminal
     */
    public function matches(Terminal\Entity $terminal): bool
    {
        foreach (self::COMPARISON_ATTRIBUTES as $key)
        {
            // For certain attributes like gateway_acquirer, null means all, hence
            // we don't match if the value for these attributes is null
            if ((in_array($key, self::NULLABLE_ATTRIBUTES, true) === true) and
                ($this->isAttributeNull($key) === true))
            {
                continue;
            }

            if ($this->match($key, $terminal) === false)
            {
                return false;
            }
        }

        return true;
    }

    protected function match(string $key, Terminal\Entity $terminal): bool
    {
        return ($this->getAttribute($key) === $terminal->getAttribute($key));
    }
}
