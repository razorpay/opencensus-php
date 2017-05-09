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

    const GATEWAY          = 'gateway';
    const MERCHANT_ID      = 'merchant_id';
    const GATEWAY_ACQUIRER = 'gateway_acquirer';
    const INTERNATIONAL    = 'international';
    const METHOD           = 'method';
    const METHOD_TYPE      = 'method_type';
    const NETWORK          = 'network';
    const ISSUER           = 'issuer';
    const LOAD             = 'load';
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
    ];

    protected $entity = 'gateway_rule';

    protected $generateIdOnCreate = true;

    protected $casts = [
        self::INTERNATIONAL => 'boolean',
        self::LOAD          => 'int',
    ];

    protected $fillable = [
        self::GATEWAY,
        self::MERCHANT_ID,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::ISSUER,
        self::LOAD,
    ];

    protected $visible = [
        self::ID,
        self::GATEWAY,
        self::MERCHANT_ID,
        self::METHOD,
        self::METHOD_TYPE,
        self::NETWORK,
        self::ISSUER,
        self::GATEWAY_ACQUIRER,
        self::INTERNATIONAL,
        self::LOAD,
        self::CREATED_AT,
        self::UPDATED_AT,
        self::DELETED_AT
    ];

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

    //-----------------Setters----------------------
    public function setLoad(int $load)
    {
        $this->setAttribute[self::LOAD] = $load;
    }

    //----------------Setters End-------------------

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
